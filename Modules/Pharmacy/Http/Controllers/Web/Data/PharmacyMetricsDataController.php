<?php

namespace Modules\Pharmacy\Http\Controllers\Web\Data;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Pharmacy\Models\Pharmacy;
use Modules\Pharmacy\Services\Web\PharmacyMetricsWebService;

class PharmacyMetricsDataController extends Controller
{
    use ApiResponse;

    public function __construct(private PharmacyMetricsWebService $metricsService) {}

    protected function resolvePharmacy(): Pharmacy
    {
        return Pharmacy::where('user_id', auth('web')->id())->firstOrFail();
    }

    public function index(): JsonResponse
    {
        $pharmacy = $this->resolvePharmacy();

        return $this->success($this->metricsService->getDashboardMetrics($pharmacy->id));
    }

    public function history(Request $request): JsonResponse
    {
        $pharmacy = $this->resolvePharmacy();

        return $this->success(
            $this->metricsService->getHistory($pharmacy->id, $request->only(['search', 'limit']))
        );
    }
}
