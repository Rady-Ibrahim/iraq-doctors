<?php

namespace Modules\Pharmacy\Http\Controllers\Api;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pharmacy\Models\Pharmacy;

class PharmacyBranchController extends Controller
{
    use ApiResponse;

    /**
     * GET /pharmacies/{id}/branches
     * قائمة فروع صيدلية معيّنة
     */
    public function index(string $id): JsonResponse
    {
        $pharmacy = Pharmacy::where('status', 'approved')->find($id);

        if (! $pharmacy) {
            return $this->notFound('الصيدلية غير موجودة');
        }

        $branches = $pharmacy->branches()
            ->with('governorate')
            ->where('is_active', true)
            ->orderBy('is_primary', 'desc')
            ->get();

        return $this->success($branches->map(fn ($b) => $this->formatBranch($b)));
    }

    /**
     * GET /pharmacies/branches/{branchId}
     * تفاصيل فرع صيدلية واحد
     */
    public function show(string $branchId): JsonResponse
    {
        $branch = \Modules\Pharmacy\Models\PharmacyBranch::with(['pharmacy', 'governorate'])
            ->where('is_active', true)
            ->find($branchId);

        if (! $branch) {
            return $this->notFound('الفرع غير موجود');
        }

        return $this->success($this->formatBranch($branch, true));
    }

    /**
     * GET /pharmacies/branches/nearby
     * أقرب فروع الصيدليات حسب الموقع
     */
    public function nearby(): JsonResponse
    {
        $latitude      = request('latitude');
        $longitude     = request('longitude');
        $radius        = (float) request('radius', 10);
        $governorateId = request('governorate_id') ? (int) request('governorate_id') : null;
        $limit         = (int) request('limit', 20);

        if (! $latitude || ! $longitude) {
            return $this->error('الموقع الجغرافي مطلوب', 'LOCATION_REQUIRED', 400);
        }

        $query = \Modules\Pharmacy\Models\PharmacyBranch::with(['pharmacy.governorate', 'governorate'])
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereHas('pharmacy', fn ($q) => $q->where('status', 'approved'));

        if ($governorateId) {
            $query->where('governorate_id', $governorateId);
        }

        $query->whereRaw("
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) *
            cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) < ?
        ", [(float) $latitude, (float) $longitude, (float) $latitude, $radius]);

        $branches = $query->orderByRaw("
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) *
            cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))
        ", [(float) $latitude, (float) $longitude, (float) $latitude])
            ->limit($limit)
            ->get();

        return $this->success($branches->map(function ($branch) use ($latitude, $longitude) {
            $distance = $this->haversineKm(
                (float) $latitude, (float) $longitude,
                (float) $branch->latitude, (float) $branch->longitude
            );

            return array_merge($this->formatBranch($branch), [
                'distance_km' => round($distance, 2),
            ]);
        }));
    }

    private function formatBranch($branch, bool $detailed = false): array
    {
        $data = [
            'id'               => $branch->id,
            'pharmacy_id'      => $branch->pharmacy_id,
            'pharmacy_name'    => $branch->pharmacy?->name,
            'pharmacy_logo'    => storage_public_url($branch->pharmacy?->logo),
            'branch_name'      => $branch->branch_name,
            'governorate_id'   => $branch->governorate_id,
            'governorate_name' => $branch->governorate?->name_ar,
            'district'         => $branch->district,
            'address'          => $branch->address,
            'latitude'         => $branch->latitude,
            'longitude'        => $branch->longitude,
            'phone'            => $branch->phone,
            'is_primary'       => $branch->is_primary,
        ];

        if ($detailed) {
            $data['working_hours'] = $branch->working_hours;
        }

        return $data;
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a    = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
