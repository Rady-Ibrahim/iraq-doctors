<?php

namespace Modules\Laboratory\Services\Web;

use Modules\Laboratory\Enums\LaboratoryOrderStatus;
use Modules\Laboratory\Models\LaboratoryOrder;

class LaboratoryMetricsWebService
{
    public function getDashboardMetrics(int $laboratoryId): array
    {
        $baseQuery = LaboratoryOrder::where('laboratory_id', $laboratoryId);

        $byStatus = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $deliveredStatus = LaboratoryOrderStatus::Delivered->value;
        $thisMonth = now()->startOfMonth();

        $deliveredThisMonth = (clone $baseQuery)
            ->where('status', $deliveredStatus)
            ->where('completed_at', '>=', $thisMonth)
            ->count();

        $revenueThisMonth = (clone $baseQuery)
            ->where('status', $deliveredStatus)
            ->where('completed_at', '>=', $thisMonth)
            ->sum('total_amount');

        $totalRevenue = (clone $baseQuery)
            ->where('status', $deliveredStatus)
            ->sum('total_amount');

        $pendingOrders = (int) (($byStatus[LaboratoryOrderStatus::New->value] ?? 0)
            + ($byStatus[LaboratoryOrderStatus::Reviewing->value] ?? 0));

        $awaitingPatient = (int) ($byStatus[LaboratoryOrderStatus::Quoted->value] ?? 0);

        $inProgress = (int) collect($byStatus)->except([
            LaboratoryOrderStatus::New->value,
            LaboratoryOrderStatus::Reviewing->value,
            LaboratoryOrderStatus::Quoted->value,
            LaboratoryOrderStatus::Delivered->value,
            LaboratoryOrderStatus::Cancelled->value,
        ])->sum();

        return [
            'orders_by_status' => collect(LaboratoryOrderStatus::cases())->map(fn ($s) => [
                'status' => $s->value,
                'label' => $s->label(),
                'count' => (int) ($byStatus[$s->value] ?? 0),
            ])->values()->all(),
            'delivered_total' => (int) ($byStatus[$deliveredStatus] ?? 0),
            'delivered_this_month' => $deliveredThisMonth,
            'revenue_this_month' => (float) $revenueThisMonth,
            'revenue_total' => (float) $totalRevenue,
            'active_orders' => (clone $baseQuery)
                ->whereNotIn('status', [LaboratoryOrderStatus::Delivered, LaboratoryOrderStatus::Cancelled])
                ->count(),
            'pending_orders' => $pendingOrders,
            'awaiting_patient' => $awaitingPatient,
            'in_progress_orders' => $inProgress,
            'catalog_tests' => \Modules\Laboratory\Models\LaboratoryTestItem::where('laboratory_id', $laboratoryId)
                ->where('is_available', true)
                ->count(),
            'catalog_alerts' => $this->getCatalogAlerts($laboratoryId),
            'recent_orders' => $this->getRecentOrders($laboratoryId),
        ];
    }

    protected function getCatalogAlerts(int $laboratoryId): array
    {
        $items = \Modules\Laboratory\Models\LaboratoryTestItem::with('labTest')
            ->where('laboratory_id', $laboratoryId)
            ->orderBy('is_available')
            ->get();

        $unavailable = $items->filter(fn ($item) => ! $item->is_available)->values();

        return [
            'unavailable_count' => $unavailable->count(),
            'unavailable_tests' => $unavailable->take(10)->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->labTest?->name_ar ?? 'تحليل',
                'price' => (float) $item->price,
            ])->values()->all(),
        ];
    }

    protected function getRecentOrders(int $laboratoryId, int $limit = 8): array
    {
        return LaboratoryOrder::with('patient')
            ->where('laboratory_id', $laboratoryId)
            ->whereNotIn('status', [LaboratoryOrderStatus::Delivered, LaboratoryOrderStatus::Cancelled])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'patient_name' => $order->patient?->name,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'total_amount' => $order->total_amount,
                'created_at' => $order->created_at?->format('Y-m-d H:i'),
                'awaiting_patient' => $order->status === LaboratoryOrderStatus::Quoted,
            ])
            ->values()
            ->all();
    }

    public function getHistory(int $laboratoryId, array $filters = []): array
    {
        $query = LaboratoryOrder::with(['patient', 'items', 'results'])
            ->where('laboratory_id', $laboratoryId)
            ->where('status', LaboratoryOrderStatus::Delivered)
            ->orderByDesc('completed_at');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn ($p) => $p->where('name', 'like', "%{$search}%"));
            });
        }

        $limit = (int) ($filters['limit'] ?? 50);

        return $query->limit($limit)->get()->map(fn ($order) => [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'patient_name' => $order->patient?->name,
            'total_amount' => $order->total_amount,
            'items_count' => $order->items->count(),
            'results_count' => $order->results->count(),
            'completed_at' => $order->completed_at?->format('Y-m-d H:i'),
            'tests' => $order->items->pluck('test_name')->all(),
        ])->values()->all();
    }
}
