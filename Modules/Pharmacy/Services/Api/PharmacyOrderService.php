<?php

namespace Modules\Pharmacy\Services\Api;

use App\Notifications\ProviderPharmacyOrderNotification;
use App\Support\OrderTimelineBuilder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Pharmacy\Enums\PharmacyOrderStatus;
use Modules\Pharmacy\Models\Pharmacy;
use Modules\Pharmacy\Models\PharmacyMedicine;
use Modules\Pharmacy\Models\PharmacyOrder;
use Modules\Pharmacy\Models\PharmacyOrderItem;
use Modules\Pharmacy\Services\Web\PharmacyOrderWebService;

class PharmacyOrderService
{
    public function createOrder(
        int $patientId,
        array $data,
        ?UploadedFile $prescriptionImage = null
    ): PharmacyOrder {
        return DB::transaction(function () use ($patientId, $data, $prescriptionImage) {
            $pharmacy = Pharmacy::where('status', 'approved')
                ->whereHas('activeSubscription')
                ->findOrFail($data['pharmacy_id']);

            $fulfillmentType = $data['fulfillment_type'] ?? 'pickup';

            if ($fulfillmentType === 'delivery' && ! $pharmacy->delivery_enabled) {
                throw new \InvalidArgumentException('هذه الصيدلية لا تدعم التوصيل حالياً');
            }

            if ($fulfillmentType === 'delivery' && empty($data['delivery_address'])) {
                throw new \InvalidArgumentException('عنوان التوصيل مطلوب');
            }

            $items = $data['items'] ?? [];
            $hasItems = is_array($items) && count($items) > 0;
            $hasImage = $prescriptionImage !== null;

            if (! $hasItems && ! $hasImage) {
                throw new \InvalidArgumentException('يجب اختيار أدوية أو إرفاق صورة روشتة');
            }

            $source = $hasImage && ! $hasItems ? 'prescription' : 'catalog';
            if ($hasImage && $hasItems) {
                $source = 'mixed';
            }

            $prescriptionPath = $prescriptionImage
                ? $prescriptionImage->store('pharmacy-orders/prescriptions', 'public')
                : null;

            $order = PharmacyOrder::create([
                'order_number' => PharmacyOrderWebService::generateOrderNumber(),
                'pharmacy_id' => $pharmacy->id,
                'patient_id' => $patientId,
                'pharmacy_branch_id' => $data['pharmacy_branch_id'] ?? null,
                'prescription_image' => $prescriptionPath,
                'fulfillment_type' => $fulfillmentType,
                'delivery_address' => $fulfillmentType === 'delivery' ? ($data['delivery_address'] ?? null) : null,
                'delivery_latitude' => $fulfillmentType === 'delivery' ? ($data['delivery_latitude'] ?? null) : null,
                'delivery_longitude' => $fulfillmentType === 'delivery' ? ($data['delivery_longitude'] ?? null) : null,
                'delivery_notes' => $fulfillmentType === 'delivery' ? ($data['delivery_notes'] ?? null) : null,
                'status' => PharmacyOrderStatus::New,
                'source' => $source,
                'patient_notes' => $data['patient_notes'] ?? null,
            ]);

            if ($hasItems) {
                $catalogIds = collect($items)->pluck('pharmacy_medicine_id')->all();
                $catalogItems = PharmacyMedicine::with('medicine')
                    ->where('pharmacy_id', $pharmacy->id)
                    ->available()
                    ->where('stock_quantity', '>', 0)
                    ->whereIn('id', $catalogIds)
                    ->get()
                    ->keyBy('id');

                if ($catalogItems->count() !== count($catalogIds)) {
                    throw new \InvalidArgumentException('بعض الأدوية غير متاحة في هذه الصيدلية');
                }

                foreach ($items as $row) {
                    $catalogItem = $catalogItems[$row['pharmacy_medicine_id']];
                    $qty = (int) ($row['quantity'] ?? 1);

                    if ($catalogItem->stock_quantity < $qty) {
                        throw new \InvalidArgumentException('الكمية المطلوبة غير متوفرة: ' . ($catalogItem->medicine?->name_ar ?? 'دواء'));
                    }

                    PharmacyOrderItem::create([
                        'pharmacy_order_id' => $order->id,
                        'pharmacy_medicine_id' => $catalogItem->id,
                        'medicine_id' => $catalogItem->medicine_id,
                        'medicine_name' => $catalogItem->medicine?->name_ar ?? 'دواء',
                        'price' => $catalogItem->price,
                        'quantity' => $qty,
                        'source' => 'patient_selected',
                    ]);
                }
            }

            return $order->fresh(['pharmacy.governorate', 'items', 'patient']);
        });
    }

    public function getPatientOrders(int $patientId, ?string $status = null, bool $historyOnly = false)
    {
        $query = PharmacyOrder::with(['pharmacy', 'items'])
            ->where('patient_id', $patientId)
            ->orderByDesc('created_at');

        if ($historyOnly) {
            $query->where('status', PharmacyOrderStatus::Completed);
        } elseif ($status) {
            $query->where('status', $status);
        } else {
            $query->whereNotIn('status', [
                PharmacyOrderStatus::Completed,
                PharmacyOrderStatus::Cancelled,
            ]);
        }

        return $query->get();
    }

    public function getPatientOrder(int $patientId, int $orderId): ?PharmacyOrder
    {
        return PharmacyOrder::with(['pharmacy.governorate', 'items', 'branch'])
            ->where('patient_id', $patientId)
            ->find($orderId);
    }

    public function cancelOrder(int $patientId, int $orderId, ?string $reason = null): PharmacyOrder
    {
        $order = $this->getPatientOrder($patientId, $orderId);

        if (! $order) {
            throw new \InvalidArgumentException('الطلب غير موجود');
        }

        $cancellable = [
            PharmacyOrderStatus::New,
            PharmacyOrderStatus::Reviewing,
            PharmacyOrderStatus::Quoted,
        ];

        if (! in_array($order->status, $cancellable, true)) {
            throw new \InvalidArgumentException('لا يمكن إلغاء الطلب في هذه الحالة');
        }

        $order->update([
            'status' => PharmacyOrderStatus::Cancelled,
            'cancel_reason' => $reason,
            'cancelled_at' => now(),
        ]);

        return $order->fresh(['pharmacy', 'items']);
    }

    public function acceptQuote(int $patientId, int $orderId): PharmacyOrder
    {
        $order = $this->getPatientOrder($patientId, $orderId);

        if (! $order) {
            throw new \InvalidArgumentException('الطلب غير موجود');
        }

        if ($order->status !== PharmacyOrderStatus::Quoted) {
            throw new \InvalidArgumentException('الطلب ليس في حالة انتظار قبول عرض السعر');
        }

        $order->update(['status' => PharmacyOrderStatus::Accepted]);

        $order = $order->fresh(['pharmacy.user', 'patient', 'items']);

        $order->pharmacy?->user?->notify(new ProviderPharmacyOrderNotification(
            $order,
            'المريض وافق على عرض السعر',
            'وافق المريض ' . ($order->patient?->name ?? '') . ' على عرض سعر الطلب رقم ' . $order->order_number . '. يمكنك بدء التجهيز.',
            'pharmacy_order_accepted'
        ));

        return $order;
    }

    public function formatOrderForPatient(PharmacyOrder $order, bool $detailed = false): array
    {
        $data = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'pharmacy_id' => $order->pharmacy_id,
            'pharmacy_name' => $order->pharmacy?->name,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'source' => $order->source,
            'fulfillment_type' => $order->fulfillment_type,
            'fulfillment_label' => $order->isDelivery() ? 'توصيل' : 'استلام من الفرع',
            'items_count' => $order->items->count(),
            'total_amount' => $order->total_amount,
            'created_at' => $order->created_at?->format('Y-m-d H:i'),
        ];

        if ($detailed) {
            $data += [
                'prescription_image' => storage_public_url($order->prescription_image),
                'patient_notes' => $order->patient_notes,
                'quote_notes' => $order->quote_notes,
                'subtotal' => $order->subtotal,
                'delivery_fee' => $order->delivery_fee,
                'delivery_address' => $order->delivery_address,
                'delivery_notes' => $order->delivery_notes,
                'quoted_at' => $order->quoted_at?->format('Y-m-d H:i'),
                'out_for_delivery_at' => $order->out_for_delivery_at?->format('Y-m-d H:i'),
                'completed_at' => $order->completed_at?->format('Y-m-d H:i'),
                'can_cancel' => in_array($order->status, [
                    PharmacyOrderStatus::New,
                    PharmacyOrderStatus::Reviewing,
                    PharmacyOrderStatus::Quoted,
                ], true),
                'can_accept_quote' => $order->status === PharmacyOrderStatus::Quoted,
                'timeline' => OrderTimelineBuilder::pharmacy(
                    $order->status,
                    $order->isDelivery(),
                    [
                        'created_at' => $order->created_at?->format('Y-m-d H:i'),
                        'quoted_at' => $order->quoted_at?->format('Y-m-d H:i'),
                        'out_for_delivery_at' => $order->out_for_delivery_at?->format('Y-m-d H:i'),
                        'completed_at' => $order->completed_at?->format('Y-m-d H:i'),
                    ]
                ),
                'items' => $order->items->map(fn ($item) => [
                    'medicine_name' => $item->medicine_name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                ])->values()->all(),
            ];
        }

        return $data;
    }
}
