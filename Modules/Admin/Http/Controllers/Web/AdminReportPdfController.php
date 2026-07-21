<?php

namespace Modules\Admin\Http\Controllers\Web;

use App\Support\PdfReport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Admin\Services\AdminDashboardService;
use Modules\Admin\Services\AdminOrdersService;

class AdminReportPdfController extends Controller
{
    public function __construct(
        private AdminOrdersService $ordersService,
        private AdminDashboardService $dashboardService,
    ) {}

    public function orders(Request $request): Response
    {
        $filters = $request->only(['date_from', 'date_to']);
        $report = $this->ordersService->getOrdersReport($filters);

        $period = 'كل الفترات';
        if (! empty($filters['date_from']) || ! empty($filters['date_to'])) {
            $period = trim(($filters['date_from'] ?? '...') . ' إلى ' . ($filters['date_to'] ?? '...'));
        }

        return PdfReport::download('pdf.admin.orders-report', [
            'title' => 'تقرير طلبات المختبرات والصيدليات',
            'subtitle' => $period,
            'report' => $report,
        ], 'orders-report-'.now()->format('Y-m-d').'.pdf');
    }

    public function revenue(Request $request): Response
    {
        $filters = $request->only(['period', 'date_from', 'date_to']);
        $report = $this->dashboardService->getRevenueDashboardData($filters);

        $periodLabels = [
            'today' => 'اليوم',
            'week' => 'هذا الأسبوع',
            'month' => 'هذا الشهر',
            'year' => 'هذه السنة',
            'custom' => 'فترة مخصصة',
        ];
        $period = $periodLabels[$filters['period'] ?? 'week'] ?? 'الفترة المحددة';
        if (($filters['period'] ?? '') === 'custom' && (! empty($filters['date_from']) || ! empty($filters['date_to']))) {
            $period .= ' ('.trim(($filters['date_from'] ?? '...').' إلى '.($filters['date_to'] ?? '...')).')';
        }

        return PdfReport::download('pdf.admin.revenue', [
            'title' => 'تقرير الإيرادات',
            'subtitle' => $period,
            'report' => $report,
        ], 'revenue-report-'.now()->format('Y-m-d').'.pdf');
    }
}
