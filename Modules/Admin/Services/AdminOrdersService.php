<?php

namespace Modules\Admin\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Laboratory\Enums\LaboratoryOrderStatus;
use Modules\Laboratory\Models\LaboratoryOrder;
use Modules\Pharmacy\Enums\PharmacyOrderStatus;
use Modules\Pharmacy\Models\PharmacyOrder;

class AdminOrdersService
{
    public function listLaboratoryOrders(array $filters = []): LengthAwarePaginator
    {
        $limit = (int) ($filters['limit'] ?? 20);

        $query = LaboratoryOrder::with(['patient', 'laboratory', 'items'])
            ->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['laboratory_id'])) {
            $query->where('laboratory_id', (int) $filters['laboratory_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn ($p) => $p->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"))
                    ->orWhereHas('laboratory', fn ($l) => $l->where('name', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['history'])) {
            $query->where('status', LaboratoryOrderStatus::Delivered);
        }

        return $query->paginate($limit);
    }

    public function getLaboratoryOrder(int $id): array
    {
        $order = LaboratoryOrder::with(['patient', 'laboratory.governorate', 'items', 'results', 'branch'])
            ->findOrFail($id);

        return $this->formatLaboratoryOrder($order, true);
    }

    public function listPharmacyOrders(array $filters = []): LengthAwarePaginator
    {
        $limit = (int) ($filters['limit'] ?? 20);

        $query = PharmacyOrder::with(['patient', 'pharmacy', 'items'])
            ->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['pharmacy_id'])) {
            $query->where('pharmacy_id', (int) $filters['pharmacy_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn ($p) => $p->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"))
                    ->orWhereHas('pharmacy', fn ($ph) => $ph->where('name', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['history'])) {
            $query->where('status', PharmacyOrderStatus::Completed);
        }

        return $query->paginate($limit);
    }

    public function getPharmacyOrder(int $id): array
    {
        $order = PharmacyOrder::with(['patient', 'pharmacy.governorate', 'items', 'branch'])
            ->findOrFail($id);

        return $this->formatPharmacyOrder($order, true);
    }

    public function getOrdersReport(array $filters = []): array
    {
        $labQuery = LaboratoryOrder::query();
        $pharmacyQuery = PharmacyOrder::query();

        if (! empty($filters['date_from'])) {
            $labQuery->whereDate('created_at', '>=', $filters['date_from']);
            $pharmacyQuery->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $labQuery->whereDate('created_at', '<=', $filters['date_to']);
            $pharmacyQuery->whereDate('created_at', '<=', $filters['date_to']);
        }

        $labCounts = (clone $labQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $pharmacyCounts = (clone $pharmacyQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $labRevenue = (clone $labQuery)
            ->where('status', LaboratoryOrderStatus::Delivered)
            ->sum('total_amount');

        $pharmacyRevenue = (clone $pharmacyQuery)
            ->where('status', PharmacyOrderStatus::Completed)
            ->sum('total_amount');

        return [
            'laboratory' => [
                'total' => array_sum($labCounts),
                'by_status' => collect(LaboratoryOrderStatus::cases())->mapWithKeys(fn ($s) => [
                    $s->value => [
                        'count' => $labCounts[$s->value] ?? 0,
                        'label' => $s->label(),
                    ],
                ])->all(),
                'delivered' => $labCounts[LaboratoryOrderStatus::Delivered->value] ?? 0,
                'revenue' => (float) $labRevenue,
            ],
            'pharmacy' => [
                'total' => array_sum($pharmacyCounts),
                'by_status' => collect(PharmacyOrderStatus::cases())->mapWithKeys(fn ($s) => [
                    $s->value => [
                        'count' => $pharmacyCounts[$s->value] ?? 0,
                        'label' => $s->label(),
                    ],
                ])->all(),
                'completed' => $pharmacyCounts[PharmacyOrderStatus::Completed->value] ?? 0,
                'revenue' => (float) $pharmacyRevenue,
            ],
            'combined_revenue' => (float) $labRevenue + (float) $pharmacyRevenue,
        ];
    }

    public function formatLaboratoryOrder(LaboratoryOrder $order, bool $detailed = false): array
    {
        $data = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'laboratory_id' => $order->laboratory_id,
            'laboratory_name' => $order->laboratory?->name,
            'patient_id' => $order->patient_id,
            'patient_name' => $order->patient?->name,
            'patient_phone' => $order->patient?->phone,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'source' => $order->source,
            'items_count' => $order->items->count(),
            'total_amount' => $order->total_amount,
            'created_at' => $order->created_at?->format('Y-m-d H:i'),
        ];

        if ($detailed) {
            $data += [
                'prescription_image' => storage_public_url($order->prescription_image),
                'patient_notes' => $order->patient_notes,
                'quote_notes' => $order->quote_notes,
                'lab_notes' => $order->lab_notes,
                'cancel_reason' => $order->cancel_reason,
                'subtotal' => $order->subtotal,
                'home_collection_fee' => $order->home_collection_fee,
                'quoted_at' => $order->quoted_at?->format('Y-m-d H:i'),
                'scheduled_at' => $order->scheduled_at?->format('Y-m-d H:i'),
                'completed_at' => $order->completed_at?->format('Y-m-d H:i'),
                'items' => $order->items->map(fn ($item) => [
                    'test_name' => $item->test_name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                ])->values()->all(),
                'results_count' => $order->relationLoaded('results') ? $order->results->count() : 0,
            ];
        }

        return $data;
    }

    public function formatPharmacyOrder(PharmacyOrder $order, bool $detailed = false): array
    {
        $data = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'pharmacy_id' => $order->pharmacy_id,
            'pharmacy_name' => $order->pharmacy?->name,
            'patient_id' => $order->patient_id,
            'patient_name' => $order->patient?->name,
            'patient_phone' => $order->patient?->phone,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'fulfillment_type' => $order->fulfillment_type,
            'source' => $order->source,
            'items_count' => $order->items->count(),
            'total_amount' => $order->total_amount,
            'created_at' => $order->created_at?->format('Y-m-d H:i'),
        ];

        if ($detailed) {
            $data += [
                'prescription_image' => storage_public_url($order->prescription_image),
                'patient_notes' => $order->patient_notes,
                'quote_notes' => $order->quote_notes,
                'pharmacy_notes' => $order->pharmacy_notes,
                'cancel_reason' => $order->cancel_reason,
                'subtotal' => $order->subtotal,
                'delivery_fee' => $order->delivery_fee,
                'delivery_address' => $order->delivery_address,
                'quoted_at' => $order->quoted_at?->format('Y-m-d H:i'),
                'completed_at' => $order->completed_at?->format('Y-m-d H:i'),
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
