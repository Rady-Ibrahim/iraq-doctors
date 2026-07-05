<?php

namespace Modules\Laboratory\Services\Web;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Notifications\LaboratoryOrderStatusChanged;
use Modules\Laboratory\Enums\LaboratoryOrderStatus;
use Modules\Laboratory\Models\Laboratory;
use Modules\Laboratory\Models\LaboratoryOrder;
use Modules\Laboratory\Models\LaboratoryOrderItem;
use Modules\Laboratory\Models\LaboratoryTestItem;

class LaboratoryOrderWebService
{
    public function listOrders(int $laboratoryId, array $filters = []): array
    {
        $query = LaboratoryOrder::with(['patient', 'items'])
            ->where('laboratory_id', $laboratoryId)
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
        $orders = $query->limit($limit)->get();

        return $orders->map(fn ($order) => $this->formatOrderSummary($order))->values()->all();
    }

    public function getOrder(int $laboratoryId, int $orderId): array
    {
        $order = $this->findOrderForLaboratory($laboratoryId, $orderId);

        return $this->formatOrderDetail($order);
    }

    public function getStatusCounts(int $laboratoryId): array
    {
        $counts = LaboratoryOrder::where('laboratory_id', $laboratoryId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $pending = ($counts[LaboratoryOrderStatus::New->value] ?? 0)
            + ($counts[LaboratoryOrderStatus::Reviewing->value] ?? 0);

        return [
            'pending' => $pending,
            'quoted' => $counts[LaboratoryOrderStatus::Quoted->value] ?? 0,
            'active' => collect($counts)->except([
                LaboratoryOrderStatus::Delivered->value,
                LaboratoryOrderStatus::Cancelled->value,
            ])->sum(),
            'delivered' => $counts[LaboratoryOrderStatus::Delivered->value] ?? 0,
            'cancelled' => $counts[LaboratoryOrderStatus::Cancelled->value] ?? 0,
        ];
    }

    public function startReview(int $laboratoryId, int $orderId): LaboratoryOrder
    {
        return $this->transitionTo($laboratoryId, $orderId, LaboratoryOrderStatus::Reviewing);
    }

    public function quote(int $laboratoryId, int $orderId, array $data): LaboratoryOrder
    {
        return DB::transaction(function () use ($laboratoryId, $orderId, $data) {
            $order = $this->findOrderForLaboratory($laboratoryId, $orderId);

            if (! in_array($order->status, [LaboratoryOrderStatus::New, LaboratoryOrderStatus::Reviewing], true)) {
                throw new \InvalidArgumentException('لا يمكن عرض السعر في هذه الحالة');
            }

            $catalogIds = collect($data['items'])->pluck('laboratory_test_item_id')->all();
            $catalogItems = LaboratoryTestItem::with('labTest')
                ->where('laboratory_id', $laboratoryId)
                ->whereIn('id', $catalogIds)
                ->get()
                ->keyBy('id');

            if ($catalogItems->count() !== count($catalogIds)) {
                throw new \InvalidArgumentException('بعض التحاليل المختارة غير تابعة لمعملك');
            }

            $isPrescriptionOrder = $order->hasPrescriptionImage() || $order->source === 'prescription';
            $itemSource = $isPrescriptionOrder ? 'lab_mapped' : 'patient_selected';

            $order->items()->delete();

            $subtotal = 0;
            foreach ($data['items'] as $row) {
                $catalogItem = $catalogItems[$row['laboratory_test_item_id']];
                $qty = (int) ($row['quantity'] ?? 1);
                $price = (float) $catalogItem->price;
                $subtotal += $price * $qty;

                LaboratoryOrderItem::create([
                    'laboratory_order_id' => $order->id,
                    'laboratory_test_item_id' => $catalogItem->id,
                    'lab_test_id' => $catalogItem->lab_test_id,
                    'test_name' => $catalogItem->labTest?->name_ar ?? 'تحليل',
                    'price' => $price,
                    'quantity' => $qty,
                    'result_hours' => $catalogItem->result_hours,
                    'source' => $itemSource,
                ]);
            }

            $homeFee = (float) ($data['home_collection_fee'] ?? 0);
            $order->update([
                'status' => LaboratoryOrderStatus::Quoted,
                'subtotal' => $subtotal,
                'home_collection_fee' => $homeFee > 0 ? $homeFee : null,
                'total_amount' => $subtotal + $homeFee,
                'quote_notes' => $data['quote_notes'] ?? null,
                'quoted_at' => now(),
                'scheduled_at' => $data['scheduled_at'] ?? $order->scheduled_at,
            ]);

            $order = $order->fresh(['patient', 'items', 'branch', 'laboratory']);
            $this->notifyPatient($order, 'تم عرض سعر طلب التحاليل رقم ' . $order->order_number);

            return $order;
        });
    }

    public function transitionTo(
        int $laboratoryId,
        int $orderId,
        LaboratoryOrderStatus $target,
        array $extra = []
    ): LaboratoryOrder {
        return DB::transaction(function () use ($laboratoryId, $orderId, $target, $extra) {
            $order = $this->findOrderForLaboratory($laboratoryId, $orderId);

            if ($target === LaboratoryOrderStatus::Accepted) {
                throw new \InvalidArgumentException('موافقة المريض على السعر تتم من تطبيق المريض فقط');
            }

            if (! $order->status->canTransitionTo($target)) {
                throw new \InvalidArgumentException('لا يمكن تغيير حالة الطلب إلى ' . $target->label());
            }

            if ($target === LaboratoryOrderStatus::Quoted && $order->items()->count() === 0) {
                throw new \InvalidArgumentException('يجب إضافة تحاليل قبل عرض السعر');
            }

            $payload = ['status' => $target];

            if ($target === LaboratoryOrderStatus::Scheduled && ! empty($extra['scheduled_at'])) {
                $payload['scheduled_at'] = $extra['scheduled_at'];
            }

            if ($target === LaboratoryOrderStatus::Cancelled) {
                $payload['cancel_reason'] = $extra['cancel_reason'] ?? null;
                $payload['cancelled_at'] = now();
            }

            if ($target === LaboratoryOrderStatus::Delivered) {
                $payload['completed_at'] = now();
            }

            if (! empty($extra['lab_notes'])) {
                $payload['lab_notes'] = $extra['lab_notes'];
            }

            $order->update($payload);

            $order = $order->fresh(['patient', 'items', 'branch', 'laboratory']);

            if ($this->shouldNotifyPatient($target)) {
                $this->notifyPatient(
                    $order,
                    'تم تحديث حالة طلب التحاليل رقم ' . $order->order_number . ' إلى: ' . $target->label()
                );
            }

            return $order;
        });
    }

    public function findOrderForLaboratory(int $laboratoryId, int $orderId): LaboratoryOrder
    {
        return LaboratoryOrder::with(['patient', 'items.labTest', 'branch', 'results'])
            ->where('laboratory_id', $laboratoryId)
            ->findOrFail($orderId);
    }

    public static function generateOrderNumber(): string
    {
        return 'LAB-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }

    protected function formatOrderSummary(LaboratoryOrder $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'patient_name' => $order->patient?->name,
            'patient_phone' => $order->patient?->phone,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'source' => $order->source,
            'has_prescription_image' => $order->hasPrescriptionImage(),
            'items_count' => $order->items->count(),
            'total_amount' => $order->total_amount,
            'created_at' => $order->created_at?->format('Y-m-d H:i'),
        ];
    }

    public function formatOrderDetail(LaboratoryOrder $order): array
    {
        $summary = $this->formatOrderSummary($order);
        $resultService = app(LaboratoryResultWebService::class);

        return array_merge($summary, [
            'prescription_image' => storage_public_url($order->prescription_image),
            'patient_notes' => $order->patient_notes,
            'quote_notes' => $order->quote_notes,
            'lab_notes' => $order->lab_notes,
            'cancel_reason' => $order->cancel_reason,
            'subtotal' => $order->subtotal,
            'home_collection_fee' => $order->home_collection_fee,
            'quoted_at' => $order->quoted_at?->format('Y-m-d H:i'),
            'awaiting_patient_acceptance' => $order->status === LaboratoryOrderStatus::Quoted,
            'scheduled_at' => $order->scheduled_at?->format('Y-m-d H:i'),
            'completed_at' => $order->completed_at?->format('Y-m-d H:i'),
            'next_statuses' => array_map(
                fn (LaboratoryOrderStatus $s) => ['value' => $s->value, 'label' => $s->label()],
                $order->status->allowedTransitions()
            ),
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'laboratory_test_item_id' => $item->laboratory_test_item_id,
                'test_name' => $item->test_name,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'line_total' => $item->lineTotal(),
                'result_hours' => $item->result_hours,
                'source' => $item->source,
            ])->values()->all(),
            'results' => $order->relationLoaded('results')
                ? $order->results->map(fn ($r) => $resultService->formatResult($r))->values()->all()
                : [],
            'can_upload_results' => $resultService->canUploadResults($order),
            'results_count' => $order->relationLoaded('results') ? $order->results->count() : 0,
        ]);
    }

    protected function shouldNotifyPatient(LaboratoryOrderStatus $status): bool
    {
        return in_array($status, [
            LaboratoryOrderStatus::Quoted,
            LaboratoryOrderStatus::Scheduled,
            LaboratoryOrderStatus::Ready,
            LaboratoryOrderStatus::Delivered,
        ], true);
    }

    protected function notifyPatient(LaboratoryOrder $order, string $message): void
    {
        $order->patient?->notify(new LaboratoryOrderStatusChanged($order, $message));
    }
}
