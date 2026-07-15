<?php

namespace Modules\Pharmacy\Services\Web;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Notifications\PharmacyOrderStatusChanged;
use App\Support\LinkedPrescription;
use Modules\MedicalRecord\Models\MedicalRecord;
use Modules\Pharmacy\Enums\PharmacyOrderStatus;
use Modules\Pharmacy\Models\PharmacyMedicine;
use Modules\Pharmacy\Models\PharmacyOrder;
use Modules\Pharmacy\Models\PharmacyOrderItem;

class PharmacyOrderWebService
{
    public function listOrders(int $pharmacyId, array $filters = []): array
    {
        $query = PharmacyOrder::with(['patient', 'items'])
            ->where('pharmacy_id', $pharmacyId)
            ->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn ($p) => $p->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        $limit = (int) ($filters['limit'] ?? 50);

        return $query->limit($limit)->get()
            ->map(fn ($order) => $this->formatOrderSummary($order))
            ->values()
            ->all();
    }

    public function getOrder(int $pharmacyId, int $orderId): array
    {
        return $this->formatOrderDetail($this->findOrderForPharmacy($pharmacyId, $orderId));
    }

    public function getStatusCounts(int $pharmacyId): array
    {
        $counts = PharmacyOrder::where('pharmacy_id', $pharmacyId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $pending = ($counts[PharmacyOrderStatus::New->value] ?? 0)
            + ($counts[PharmacyOrderStatus::Reviewing->value] ?? 0);

        return [
            'pending' => $pending,
            'quoted' => $counts[PharmacyOrderStatus::Quoted->value] ?? 0,
            'active' => collect($counts)->except([
                PharmacyOrderStatus::Completed->value,
                PharmacyOrderStatus::Cancelled->value,
            ])->sum(),
            'completed' => $counts[PharmacyOrderStatus::Completed->value] ?? 0,
            'cancelled' => $counts[PharmacyOrderStatus::Cancelled->value] ?? 0,
        ];
    }

    public function startReview(int $pharmacyId, int $orderId): PharmacyOrder
    {
        return $this->transitionTo($pharmacyId, $orderId, PharmacyOrderStatus::Reviewing);
    }

    public function quote(int $pharmacyId, int $orderId, array $data): PharmacyOrder
    {
        return DB::transaction(function () use ($pharmacyId, $orderId, $data) {
            $order = $this->findOrderForPharmacy($pharmacyId, $orderId);

            if (! in_array($order->status, [PharmacyOrderStatus::New, PharmacyOrderStatus::Reviewing], true)) {
                throw new \InvalidArgumentException('لا يمكن عرض السعر في هذه الحالة');
            }

            $catalogIds = collect($data['items'])->pluck('pharmacy_medicine_id')->all();
            $catalogItems = PharmacyMedicine::with('medicine')
                ->where('pharmacy_id', $pharmacyId)
                ->where('is_available', true)
                ->whereIn('id', $catalogIds)
                ->get()
                ->keyBy('id');

            if ($catalogItems->count() !== count($catalogIds)) {
                throw new \InvalidArgumentException('بعض الأدوية المختارة غير متاحة في صيدليتك');
            }

            foreach ($data['items'] as $row) {
                $catalogItem = $catalogItems[$row['pharmacy_medicine_id']];
                $qty = (int) ($row['quantity'] ?? 1);
                if ($catalogItem->stock_quantity < $qty) {
                    throw new \InvalidArgumentException('الكمية المطلوبة غير متوفرة في المخزون: ' . ($catalogItem->medicine?->name_ar ?? 'دواء'));
                }
            }

            $isPrescriptionOrder = $order->hasPrescriptionImage() || $order->source === 'prescription';
            $itemSource = $isPrescriptionOrder ? 'pharmacy_mapped' : 'patient_selected';

            $order->items()->delete();

            $subtotal = 0;
            foreach ($data['items'] as $row) {
                $catalogItem = $catalogItems[$row['pharmacy_medicine_id']];
                $qty = (int) ($row['quantity'] ?? 1);
                $price = (float) $catalogItem->price;
                $subtotal += $price * $qty;

                PharmacyOrderItem::create([
                    'pharmacy_order_id' => $order->id,
                    'pharmacy_medicine_id' => $catalogItem->id,
                    'medicine_id' => $catalogItem->medicine_id,
                    'medicine_name' => $catalogItem->medicine?->name_ar ?? 'دواء',
                    'price' => $price,
                    'quantity' => $qty,
                    'source' => $itemSource,
                ]);
            }

            $deliveryFee = 0.0;
            if ($order->isDelivery()) {
                $deliveryFee = (float) ($data['delivery_fee'] ?? 0);
            }

            $order->update([
                'status' => PharmacyOrderStatus::Quoted,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee > 0 ? $deliveryFee : null,
                'total_amount' => $subtotal + $deliveryFee,
                'quote_notes' => $data['quote_notes'] ?? null,
                'quoted_at' => now(),
            ]);

            $order = $order->fresh(['patient', 'items', 'branch', 'pharmacy']);
            $this->notifyPatient($order, 'تم عرض سعر طلب الأدوية رقم ' . $order->order_number);

            return $order;
        });
    }

    public function transitionTo(
        int $pharmacyId,
        int $orderId,
        PharmacyOrderStatus $target,
        array $extra = []
    ): PharmacyOrder {
        return DB::transaction(function () use ($pharmacyId, $orderId, $target, $extra) {
            $order = $this->findOrderForPharmacy($pharmacyId, $orderId);

            if ($target === PharmacyOrderStatus::Accepted) {
                throw new \InvalidArgumentException('موافقة المريض على السعر تتم من تطبيق المريض فقط');
            }

            if (! $order->status->canTransitionTo($target, $order->fulfillment_type)) {
                throw new \InvalidArgumentException('لا يمكن تغيير حالة الطلب إلى ' . $target->label());
            }

            if ($target === PharmacyOrderStatus::Quoted && $order->items()->count() === 0) {
                throw new \InvalidArgumentException('يجب إضافة أدوية قبل عرض السعر');
            }

            $payload = ['status' => $target];

            if ($target === PharmacyOrderStatus::Cancelled) {
                $payload['cancel_reason'] = $extra['cancel_reason'] ?? null;
                $payload['cancelled_at'] = now();
            }

            if ($target === PharmacyOrderStatus::OutForDelivery) {
                $payload['out_for_delivery_at'] = now();
            }

            if ($target === PharmacyOrderStatus::Completed) {
                $this->deductStock($order);
                $payload['completed_at'] = now();
            }

            if (! empty($extra['pharmacy_notes'])) {
                $payload['pharmacy_notes'] = $extra['pharmacy_notes'];
            }

            $order->update($payload);

            $order = $order->fresh(['patient', 'items', 'branch', 'pharmacy']);

            if ($target === PharmacyOrderStatus::Completed) {
                $this->syncMedicalRecord($order);
            }

            if ($this->shouldNotifyPatient($target)) {
                $this->notifyPatient(
                    $order,
                    'تم تحديث حالة طلب الأدوية رقم ' . $order->order_number . ' إلى: ' . $target->label()
                );
            }

            return $order;
        });
    }

    protected function deductStock(PharmacyOrder $order): void
    {
        foreach ($order->items as $item) {
            if (! $item->pharmacy_medicine_id) {
                continue;
            }

            $stock = PharmacyMedicine::where('pharmacy_id', $order->pharmacy_id)
                ->where('id', $item->pharmacy_medicine_id)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                continue;
            }

            $newQty = max(0, $stock->stock_quantity - $item->quantity);
            $stock->update([
                'stock_quantity' => $newQty,
                'is_available' => $newQty > 0 && $stock->is_available,
            ]);
        }
    }

    public function findOrderForPharmacy(int $pharmacyId, int $orderId): PharmacyOrder
    {
        return PharmacyOrder::with(['patient', 'items.medicine', 'branch', 'prescriptionRecord.doctor.user'])
            ->where('pharmacy_id', $pharmacyId)
            ->findOrFail($orderId);
    }

    public static function generateOrderNumber(): string
    {
        return 'PHR-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }

    protected function formatOrderSummary(PharmacyOrder $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'patient_name' => $order->patient?->name,
            'patient_phone' => $order->patient?->phone,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'source' => $order->source,
            'fulfillment_type' => $order->fulfillment_type,
            'fulfillment_label' => $order->isDelivery() ? 'توصيل' : 'استلام من الفرع',
            'has_prescription_image' => $order->hasPrescriptionImage(),
            'items_count' => $order->items->count(),
            'total_amount' => $order->total_amount,
            'created_at' => $order->created_at?->format('Y-m-d H:i'),
        ];
    }

    public function formatOrderDetail(PharmacyOrder $order): array
    {
        $summary = $this->formatOrderSummary($order);

        return array_merge($summary, [
            'prescription_image' => storage_public_url($order->prescription_image),
            'doctor_prescription' => LinkedPrescription::format($order->prescriptionRecord),
            'patient_notes' => $order->patient_notes,
            'quote_notes' => $order->quote_notes,
            'pharmacy_notes' => $order->pharmacy_notes,
            'cancel_reason' => $order->cancel_reason,
            'subtotal' => $order->subtotal,
            'delivery_fee' => $order->delivery_fee,
            'delivery_address' => $order->delivery_address,
            'delivery_notes' => $order->delivery_notes,
            'quoted_at' => $order->quoted_at?->format('Y-m-d H:i'),
            'awaiting_patient_acceptance' => $order->status === PharmacyOrderStatus::Quoted,
            'out_for_delivery_at' => $order->out_for_delivery_at?->format('Y-m-d H:i'),
            'completed_at' => $order->completed_at?->format('Y-m-d H:i'),
            'next_statuses' => array_map(
                fn (PharmacyOrderStatus $s) => ['value' => $s->value, 'label' => $s->label()],
                $order->status->allowedTransitions($order->fulfillment_type)
            ),
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'pharmacy_medicine_id' => $item->pharmacy_medicine_id,
                'medicine_name' => $item->medicine_name,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'line_total' => $item->lineTotal(),
                'source' => $item->source,
            ])->values()->all(),
        ]);
    }

    protected function syncMedicalRecord(PharmacyOrder $order): MedicalRecord
    {
        $order->loadMissing(['pharmacy', 'items']);

        $medicines = $order->items->map(fn ($item) => [
            'name' => $item->medicine_name,
            'price' => $item->price,
            'quantity' => $item->quantity,
        ])->values()->all();

        return MedicalRecord::firstOrCreate(
            ['pharmacy_order_id' => $order->id],
            [
                'patient_id' => $order->patient_id,
                'pharmacy_id' => $order->pharmacy_id,
                'record_type' => 'pharmacy_order',
                'diagnosis' => 'طلب أدوية — ' . $order->order_number,
                'prescription' => $medicines,
                'notes' => json_encode([
                    'order_number' => $order->order_number,
                    'pharmacy_name' => $order->pharmacy?->name,
                    'fulfillment_type' => $order->fulfillment_type,
                    'total_amount' => $order->total_amount,
                ], JSON_UNESCAPED_UNICODE),
                'attachments' => $order->prescription_image
                    ? [[
                        'file_name' => basename($order->prescription_image),
                        'file_path' => storage_public_url($order->prescription_image),
                        'file_type' => 'image',
                        'uploaded_at' => $order->created_at?->toDateTimeString(),
                    ]]
                    : [],
                'created_by' => $order->pharmacy?->user_id ?? $order->patient_id,
            ]
        );
    }

    protected function shouldNotifyPatient(PharmacyOrderStatus $status): bool
    {
        return in_array($status, [
            PharmacyOrderStatus::Quoted,
            PharmacyOrderStatus::Preparing,
            PharmacyOrderStatus::OutForDelivery,
            PharmacyOrderStatus::Completed,
        ], true);
    }

    protected function notifyPatient(PharmacyOrder $order, string $message): void
    {
        $order->patient?->notify(new PharmacyOrderStatusChanged($order, $message));
    }
}
