<?php

namespace Modules\Laboratory\Http\Controllers\Api;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Laboratory\Http\Requests\Api\SearchLaboratoriesRequest;
use Modules\Laboratory\Services\Api\LaboratoryService;

class LaboratoryController extends Controller
{
    use ApiResponse;

    public function __construct(private LaboratoryService $laboratoryService) {}

    public function index(SearchLaboratoriesRequest $request): JsonResponse
    {
        $limit = (int) ($request->limit ?? 20);
        $paginator = $this->laboratoryService->search($request->validated())->paginate($limit);

        return $this->paginated(
            collect($paginator->items())->map(fn ($lab) => $this->laboratoryService->formatLaboratory($lab))->all(),
            $paginator->total(),
            $paginator->currentPage(),
            $limit
        );
    }

    public function nearby(): JsonResponse
    {
        $latitude = request('latitude');
        $longitude = request('longitude');
        $radius = (float) request('radius', 10);
        $governorateId = request('governorate_id') ? (int) request('governorate_id') : null;
        $limit = (int) request('limit', 20);

        if (! $latitude || ! $longitude) {
            return $this->error('الموقع الجغرافي مطلوب', 'LOCATION_REQUIRED', 400);
        }

        $results = $this->laboratoryService->getNearby(
            (float) $latitude,
            (float) $longitude,
            $radius,
            $governorateId
        );

        $items = collect($results)
            ->take($limit)
            ->map(fn ($row) => $this->laboratoryService->formatLaboratory(
                $row['laboratory'],
                false,
                $row['distance_km']
            ))
            ->values()
            ->all();

        return $this->success($items);
    }

    public function show(string $id): JsonResponse
    {
        $laboratory = $this->laboratoryService->getProfile((int) $id);

        if (! $laboratory) {
            return $this->notFound('المختبر غير موجود');
        }

        return $this->success($this->laboratoryService->formatLaboratory($laboratory, true));
    }

    public function tests(string $id): JsonResponse
    {
        $laboratory = $this->laboratoryService->getProfile((int) $id);

        if (! $laboratory) {
            return $this->notFound('المختبر غير موجود');
        }

        $categoryId = request('category_id') ? (int) request('category_id') : null;
        $items = $this->laboratoryService->getTestCatalog((int) $id, $categoryId);

        return $this->success($items->map(fn ($item) => $this->laboratoryService->formatTestItem($item))->values()->all());
    }
}
