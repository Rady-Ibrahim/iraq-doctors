<?php

namespace Modules\Laboratory\Services\Api;

use App\Notifications\ProviderLaboratoryOrderNotification;
use App\Support\OrderTimelineBuilder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Laboratory\Enums\LaboratoryOrderStatus;
use Modules\Laboratory\Models\Laboratory;
use Modules\Laboratory\Models\LaboratoryOrder;
use Modules\Laboratory\Models\LaboratoryOrderItem;
use Modules\Laboratory\Models\LaboratoryTestItem;
use Modules\Laboratory\Services\Web\LaboratoryOrderWebService;

class LaboratoryOrderService
{
    public function createOrder(
        int $patientId,
        array $data,
        ?UploadedFile $prescriptionImage = null
    ): LaboratoryOrder {
        return DB::transaction(function () use ($patientId, $data, $prescriptionImage) {
            $laboratory = Laboratory::where('status', 'approved')
                ->whereHas('activeSubscription')
                ->findOrFail($data['laboratory_id']);

            $items = $data['items'] ?? [];
            $hasItems = is_array($items) && count($items) > 0;
            $hasImage = $prescriptionImage !== null;

            if (! $hasItems && ! $hasImage) {
                throw new \InvalidArgumentException('يجب اختيار تحاليل أو إرفاق صورة روشتة');
            }

            $source = $hasImage && ! $hasItems ? 'prescription' : 'catalog';
            if ($hasImage && $hasItems) {
                $source = 'mixed';
            }

            $prescriptionPath = $prescriptionImage
                ? $prescriptionImage->store('laboratory-orders/prescriptions', 'public')
                : null;

            $order = LaboratoryOrder::create([
                'order_number' => LaboratoryOrderWebService::generateOrderNumber(),
                'laboratory_id' => $laboratory->id,
                'patient_id' => $patientId,
                'laboratory_branch_id' => $data['laboratory_branch_id'] ?? null,
                'prescription_image' => $prescriptionPath,
                'status' => LaboratoryOrderStatus::New,
                'source' => $source,
                'patient_notes' => $data['patient_notes'] ?? null,
            ]);

            if ($hasItems) {
                $catalogIds = collect($items)->pluck('laboratory_test_item_id')->all();
                $catalogItems = LaboratoryTestItem::with('labTest')
                    ->where('laboratory_id', $laboratory->id)
                    ->available()
                    ->whereIn('id', $catalogIds)
                    ->get()
                    ->keyBy('id');

                if ($catalogItems->count() !== count($catalogIds)) {
                    throw new \InvalidArgumentException('بعض التحاليل غير متاحة في هذا المعمل');
                }

                foreach ($items as $row) {
                    $catalogItem = $catalogItems[$row['laboratory_test_item_id']];
                    $qty = (int) ($row['quantity'] ?? 1);

                    LaboratoryOrderItem::create([
                        'laboratory_order_id' => $order->id,
                        'laboratory_test_item_id' => $catalogItem->id,
                        'lab_test_id' => $catalogItem->lab_test_id,
                        'test_name' => $catalogItem->labTest?->name_ar ?? 'تحليل',
                        'price' => $catalogItem->price,
                        'quantity' => $qty,
                        'result_hours' => $catalogItem->result_hours,
                        'source' => 'patient_selected',
                    ]);
                }
            }

            return $order->fresh(['laboratory.governorate', 'items', 'patient']);
        });
    }

    public function getPatientOrders(int $patientId, ?string $status = null, bool $historyOnly = false)
    {
        $query = LaboratoryOrder::with(['laboratory', 'items'])
            ->where('patient_id', $patientId)
            ->orderByDesc('created_at');

        if ($historyOnly) {
            $query->where('status', LaboratoryOrderStatus::Delivered);
        } elseif ($status) {
            $query->where('status', $status);
        } else {
            $query->whereNotIn('status', [
                LaboratoryOrderStatus::Delivered,
                LaboratoryOrderStatus::Cancelled,
            ]);
        }

        return $query->get();
    }

    public function getPatientOrder(int $patientId, int $orderId): ?LaboratoryOrder
    {
        return LaboratoryOrder::with(['laboratory.governorate', 'items', 'results', 'branch'])
            ->where('patient_id', $patientId)
            ->find($orderId);
    }

    public function cancelOrder(int $patientId, int $orderId, ?string $reason = null): LaboratoryOrder
    {
        $order = $this->getPatientOrder($patientId, $orderId);

        if (! $order) {
            throw new \InvalidArgumentException('الطلب غير موجود');
        }

        $cancellable = [
            LaboratoryOrderStatus::New,
            LaboratoryOrderStatus::Reviewing,
            LaboratoryOrderStatus::Quoted,
        ];

        if (! in_array($order->status, $cancellable, true)) {
            throw new \InvalidArgumentException('لا يمكن إلغاء الطلب في هذه الحالة');
        }

        $order->update([
            'status' => LaboratoryOrderStatus::Cancelled,
            'cancel_reason' => $reason,
            'cancelled_at' => now(),
        ]);

        return $order->fresh(['laboratory', 'items']);
    }

    public function acceptQuote(int $patientId, int $orderId): LaboratoryOrder
    {
        $order = $this->getPatientOrder($patientId, $orderId);

        if (! $order) {
            throw new \InvalidArgumentException('الطلب غير موجود');
        }

        if ($order->status !== LaboratoryOrderStatus::Quoted) {
            throw new \InvalidArgumentException('الطلب ليس في حالة انتظار قبول عرض السعر');
        }

        $order->update(['status' => LaboratoryOrderStatus::Accepted]);

        $order = $order->fresh(['laboratory.user', 'patient', 'items']);

        $order->laboratory?->user?->notify(new ProviderLaboratoryOrderNotification(
            $order,
            'المريض وافق على عرض السعر',
            'وافق المريض ' . ($order->patient?->name ?? '') . ' على عرض سعر الطلب رقم ' . $order->order_number . '. يمكنك متابعة التنفيذ.',
            'laboratory_order_accepted'
        ));

        return $order;
    }

    public function formatOrderForPatient(LaboratoryOrder $order, bool $detailed = false): array
    {
        $data = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'laboratory_id' => $order->laboratory_id,
            'laboratory_name' => $order->laboratory?->name,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'source' => $order->source,
            'items_count' => $order->items->count(),
            'total_amount' => $order->total_amount,
            'created_at' => $order->created_at?->format('Y-m-d H:i'),
        ];

        if ($detailed) {
            $canSeeResults = in_array($order->status, [
                LaboratoryOrderStatus::Ready,
                LaboratoryOrderStatus::Delivered,
            ], true);

            $data += [
                'prescription_image' => storage_public_url($order->prescription_image),
                'patient_notes' => $order->patient_notes,
                'quote_notes' => $order->quote_notes,
                'subtotal' => $order->subtotal,
                'home_collection_fee' => $order->home_collection_fee,
                'quoted_at' => $order->quoted_at?->format('Y-m-d H:i'),
                'scheduled_at' => $order->scheduled_at?->format('Y-m-d H:i'),
                'completed_at' => $order->completed_at?->format('Y-m-d H:i'),
                'can_cancel' => in_array($order->status, [
                    LaboratoryOrderStatus::New,
                    LaboratoryOrderStatus::Reviewing,
                    LaboratoryOrderStatus::Quoted,
                ], true),
                'can_accept_quote' => $order->status === LaboratoryOrderStatus::Quoted,
                'timeline' => OrderTimelineBuilder::laboratory(
                    $order->status,
                    [
                        'created_at' => $order->created_at?->format('Y-m-d H:i'),
                        'quoted_at' => $order->quoted_at?->format('Y-m-d H:i'),
                        'scheduled_at' => $order->scheduled_at?->format('Y-m-d H:i'),
                        'completed_at' => $order->completed_at?->format('Y-m-d H:i'),
                    ]
                ),
                'items' => $order->items->map(fn ($item) => [
                    'test_name' => $item->test_name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'result_hours' => $item->result_hours,
                ])->values()->all(),
                'results' => $canSeeResults && $order->relationLoaded('results')
                    ? $order->results->map(fn ($r) => [
                        'id' => $r->id,
                        'file_name' => $r->file_name,
                        'file_url' => storage_public_url($r->file_path),
                        'created_at' => $r->created_at?->format('Y-m-d H:i'),
                    ])->values()->all()
                    : [],
            ];
        }

        return $data;
    }
}
