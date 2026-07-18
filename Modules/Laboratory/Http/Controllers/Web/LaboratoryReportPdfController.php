<?php

namespace Modules\Laboratory\Http\Controllers\Web;

use App\Support\PdfReport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Laboratory\Models\Laboratory;
use Modules\Laboratory\Services\Web\LaboratoryMetricsWebService;

class LaboratoryReportPdfController extends Controller
{
    public function __construct(private LaboratoryMetricsWebService $metricsService) {}

    public function download(Request $request): Response
    {
        $laboratory = Laboratory::where('user_id', auth('web')->id())->firstOrFail();
        $metrics = $this->metricsService->getDashboardMetrics($laboratory->id);
        $history = $this->metricsService->getHistory($laboratory->id, [
            'search' => $request->get('search'),
            'limit' => (int) ($request->get('limit') ?? 100),
        ]);

        return PdfReport::download('pdf.laboratory.report', [
            'title' => 'تقرير المختبر',
            'subtitle' => $laboratory->name,
            'metrics' => $metrics,
            'history' => $history,
        ], 'laboratory-report-'.now()->format('Y-m-d').'.pdf');
    }
}
