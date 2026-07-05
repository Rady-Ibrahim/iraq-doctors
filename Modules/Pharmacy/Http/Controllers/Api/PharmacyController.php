<?php

namespace Modules\Pharmacy\Http\Controllers\Api;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pharmacy\Http\Requests\Api\SearchPharmaciesRequest;
use Modules\Pharmacy\Services\Api\PharmacyService;

class PharmacyController extends Controller
{
    use ApiResponse;

    public function __construct(private PharmacyService $pharmacyService) {}

    public function index(SearchPharmaciesRequest $request): JsonResponse
    {
        $limit = (int) ($request->limit ?? 20);
        $paginator = $this->pharmacyService->search($request->validated())->paginate($limit);

        return $this->paginated(
            collect($paginator->items())->map(fn ($pharmacy) => $this->pharmacyService->formatPharmacy($pharmacy))->all(),
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

        $results = $this->pharmacyService->getNearby(
            (float) $latitude,
            (float) $longitude,
            $radius,
            $governorateId
        );

        $items = collect($results)
            ->take($limit)
            ->map(fn ($row) => $this->pharmacyService->formatPharmacy(
                $row['pharmacy'],
                false,
                $row['distance_km']
            ))
            ->values()
            ->all();

        return $this->success($items);
    }

    public function show(string $id): JsonResponse
    {
        $pharmacy = $this->pharmacyService->getProfile((int) $id);

        if (! $pharmacy) {
            return $this->notFound('الصيدلية غير موجودة');
        }

        return $this->success($this->pharmacyService->formatPharmacy($pharmacy, true));
    }

    public function medicines(string $id): JsonResponse
    {
        $pharmacy = $this->pharmacyService->getProfile((int) $id);

        if (! $pharmacy) {
            return $this->notFound('الصيدلية غير موجودة');
        }

        $categoryId = request('category_id') ? (int) request('category_id') : null;
        $items = $this->pharmacyService->getMedicineCatalog((int) $id, $categoryId);

        return $this->success($items->map(fn ($item) => $this->pharmacyService->formatMedicineItem($item))->values()->all());
    }

    public function compareMedicinePrices(): JsonResponse
    {
        $medicineId = request('medicine_id');
        if (! $medicineId) {
            return $this->error('معرّف الدواء مطلوب', 'MEDICINE_ID_REQUIRED', 400);
        }

        $governorateId = request('governorate_id') ? (int) request('governorate_id') : null;
        $latitude = request('latitude') ? (float) request('latitude') : null;
        $longitude = request('longitude') ? (float) request('longitude') : null;

        $results = $this->pharmacyService->compareMedicinePrices(
            (int) $medicineId,
            $governorateId,
            $latitude,
            $longitude
        );

        return $this->success([
            'medicine_id' => (int) $medicineId,
            'offers_count' => count($results),
            'offers' => $results,
        ]);
    }
}
