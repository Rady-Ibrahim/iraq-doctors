<?php

namespace Modules\Laboratory\Http\Controllers\Web\Data;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Laboratory\Http\Requests\Web\StoreLaboratoryOrderResultRequest;
use Modules\Laboratory\Models\Laboratory;
use Modules\Laboratory\Services\Web\LaboratoryMetricsWebService;
use Modules\Laboratory\Services\Web\LaboratoryResultWebService;

class LaboratoryMetricsDataController extends Controller
{
    use ApiResponse;

    public function __construct(private LaboratoryMetricsWebService $metricsService) {}

    protected function resolveLaboratory(): Laboratory
    {
        return Laboratory::where('user_id', auth('web')->id())->firstOrFail();
    }

    public function index(): JsonResponse
    {
        $laboratory = $this->resolveLaboratory();

        return $this->success($this->metricsService->getDashboardMetrics($laboratory->id));
    }

    public function history(Request $request): JsonResponse
    {
        $laboratory = $this->resolveLaboratory();

        return $this->success(
            $this->metricsService->getHistory($laboratory->id, $request->only(['search', 'limit']))
        );
    }
}
