<?php

namespace Modules\Pharmacy\Services\Web;

use Modules\Pharmacy\Enums\PharmacyOrderStatus;
use Modules\Pharmacy\Models\PharmacyMedicine;
use Modules\Pharmacy\Models\PharmacyOrder;

class PharmacyMetricsWebService
{
    public const LOW_STOCK_THRESHOLD = 10;
    public const EXPIRY_WARNING_DAYS = 30;

    public function getDashboardMetrics(int $pharmacyId): array
    {
        $baseQuery = PharmacyOrder::where('pharmacy_id', $pharmacyId);

        $byStatus = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $completedStatus = PharmacyOrderStatus::Completed->value;
        $thisMonth = now()->startOfMonth();

        $completedThisMonth = (clone $baseQuery)
            ->where('status', $completedStatus)
            ->where('completed_at', '>=', $thisMonth)
            ->count();

        $revenueThisMonth = (clone $baseQuery)
            ->where('status', $completedStatus)
            ->where('completed_at', '>=', $thisMonth)
            ->sum('total_amount');

        $totalRevenue = (clone $baseQuery)
            ->where('status', $completedStatus)
            ->sum('total_amount');

        $pendingOrders = (int) (($byStatus[PharmacyOrderStatus::New->value] ?? 0)
            + ($byStatus[PharmacyOrderStatus::Reviewing->value] ?? 0));

        $awaitingPatient = (int) ($byStatus[PharmacyOrderStatus::Quoted->value] ?? 0);

        $inProgress = (int) (($byStatus[PharmacyOrderStatus::Accepted->value] ?? 0)
            + ($byStatus[PharmacyOrderStatus::Preparing->value] ?? 0)
            + ($byStatus[PharmacyOrderStatus::OutForDelivery->value] ?? 0));

        return [
            'orders_by_status' => collect(PharmacyOrderStatus::cases())->map(fn ($s) => [
                'status' => $s->value,
                'label' => $s->label(),
                'count' => (int) ($byStatus[$s->value] ?? 0),
            ])->values()->all(),
            'completed_total' => (int) ($byStatus[$completedStatus] ?? 0),
            'completed_this_month' => $completedThisMonth,
            'revenue_this_month' => (float) $revenueThisMonth,
            'revenue_total' => (float) $totalRevenue,
            'active_orders' => (clone $baseQuery)
                ->whereNotIn('status', [PharmacyOrderStatus::Completed, PharmacyOrderStatus::Cancelled])
                ->count(),
            'pending_orders' => $pendingOrders,
            'awaiting_patient' => $awaitingPatient,
            'in_progress_orders' => $inProgress,
            'catalog_medicines' => PharmacyMedicine::where('pharmacy_id', $pharmacyId)
                ->where('is_available', true)
                ->count(),
            'delivery_orders' => (clone $baseQuery)
                ->where('fulfillment_type', 'delivery')
                ->whereNotIn('status', [PharmacyOrderStatus::Completed, PharmacyOrderStatus::Cancelled])
                ->count(),
            'stock_alerts' => $this->getStockAlerts($pharmacyId),
            'expiry_alerts' => $this->getExpiryAlerts($pharmacyId),
            'recent_orders' => $this->getRecentOrders($pharmacyId),
        ];
    }

    protected function getStockAlerts(int $pharmacyId): array
    {
        $medicines = PharmacyMedicine::with('medicine')
            ->where('pharmacy_id', $pharmacyId)
            ->orderBy('stock_quantity')
            ->get();

        $lowStock = [];
        $outOfStock = [];

        foreach ($medicines as $item) {
            $row = [
                'id' => $item->id,
                'name' => $item->medicine?->name_ar ?? 'دواء',
                'stock_quantity' => (int) $item->stock_quantity,
                'is_available' => (bool) $item->is_available,
                'price' => (float) $item->price,
            ];

            if ($item->stock_quantity <= 0) {
                $outOfStock[] = $row;
            } elseif ($item->stock_quantity <= self::LOW_STOCK_THRESHOLD) {
                $lowStock[] = $row;
            }
        }

        return [
            'low_stock_threshold' => self::LOW_STOCK_THRESHOLD,
            'low_stock_count' => count($lowStock),
            'out_of_stock_count' => count($outOfStock),
            'low_stock' => array_slice($lowStock, 0, 10),
            'out_of_stock' => array_slice($outOfStock, 0, 10),
        ];
    }

    protected function getExpiryAlerts(int $pharmacyId): array
    {
        $today = now()->startOfDay();
        $warningDate = now()->addDays(self::EXPIRY_WARNING_DAYS)->endOfDay();

        $medicines = PharmacyMedicine::with('medicine')
            ->where('pharmacy_id', $pharmacyId)
            ->whereNotNull('expiry_date')
            ->orderBy('expiry_date')
            ->get();

        $expired = [];
        $expiringSoon = [];

        foreach ($medicines as $item) {
            if (! $item->expiry_date) {
                continue;
            }

            $row = [
                'id' => $item->id,
                'name' => $item->medicine?->name_ar ?? 'دواء',
                'expiry_date' => $item->expiry_date->format('Y-m-d'),
                'days_left' => (int) $today->diffInDays($item->expiry_date, false),
                'stock_quantity' => (int) $item->stock_quantity,
            ];

            if ($item->expiry_date->lt($today)) {
                $expired[] = $row;
            } elseif ($item->expiry_date->lte($warningDate)) {
                $expiringSoon[] = $row;
            }
        }

        return [
            'warning_days' => self::EXPIRY_WARNING_DAYS,
            'expired_count' => count($expired),
            'expiring_soon_count' => count($expiringSoon),
            'expired' => array_slice($expired, 0, 10),
            'expiring_soon' => array_slice($expiringSoon, 0, 10),
        ];
    }

    protected function getRecentOrders(int $pharmacyId, int $limit = 8): array
    {
        return PharmacyOrder::with('patient')
            ->where('pharmacy_id', $pharmacyId)
            ->whereNotIn('status', [PharmacyOrderStatus::Completed, PharmacyOrderStatus::Cancelled])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'patient_name' => $order->patient?->name,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'fulfillment_type' => $order->fulfillment_type,
                'total_amount' => $order->total_amount,
                'created_at' => $order->created_at?->format('Y-m-d H:i'),
                'awaiting_patient' => $order->status === PharmacyOrderStatus::Quoted,
            ])
            ->values()
            ->all();
    }

    public function getHistory(int $pharmacyId, array $filters = []): array
    {
        $query = PharmacyOrder::with(['patient', 'items'])
            ->where('pharmacy_id', $pharmacyId)
            ->where('status', PharmacyOrderStatus::Completed)
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
            'fulfillment_type' => $order->fulfillment_type,
            'fulfillment_label' => $order->isDelivery() ? 'توصيل' : 'استلام',
            'total_amount' => $order->total_amount,
            'items_count' => $order->items->count(),
            'completed_at' => $order->completed_at?->format('Y-m-d H:i'),
        ])->values()->all();
    }
}
