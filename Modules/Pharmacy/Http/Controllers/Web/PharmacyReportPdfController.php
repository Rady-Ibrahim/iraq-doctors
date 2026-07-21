<?php

namespace Modules\Pharmacy\Http\Controllers\Web;

use App\Support\PdfReport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Pharmacy\Models\Pharmacy;
use Modules\Pharmacy\Services\Web\PharmacyMetricsWebService;

class PharmacyReportPdfController extends Controller
{
    public function __construct(private PharmacyMetricsWebService $metricsService) {}

    public function download(Request $request): Response
    {
        $pharmacy = Pharmacy::where('user_id', auth('web')->id())->firstOrFail();
        $metrics = $this->metricsService->getDashboardMetrics($pharmacy->id);
        $history = $this->metricsService->getHistory($pharmacy->id, [
            'search' => $request->get('search'),
            'limit' => (int) ($request->get('limit') ?? 100),
        ]);

        return PdfReport::download('pdf.pharmacy.report', [
            'title' => 'تقرير الصيدلية',
            'subtitle' => $pharmacy->name,
            'metrics' => $metrics,
            'history' => $history,
        ], 'pharmacy-report-'.now()->format('Y-m-d').'.pdf');
    }
}
