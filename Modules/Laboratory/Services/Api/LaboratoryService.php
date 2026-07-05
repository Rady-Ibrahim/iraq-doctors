<?php

namespace Modules\Laboratory\Services\Api;

use Illuminate\Database\Eloquent\Builder;
use Modules\Laboratory\Models\Laboratory;
use Modules\Laboratory\Models\LaboratoryTestItem;

class LaboratoryService
{
    public function search(array $filters): Builder
    {
        $query = Laboratory::with(['governorate', 'activeSubscription'])
            ->where('status', 'approved')
            ->whereHas('activeSubscription');

        if (! empty($filters['governorate_id'])) {
            $query->where('governorate_id', $filters['governorate_id']);
        }

        if (! empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (! empty($filters['home_collection'])) {
            $query->where('home_collection_enabled', true);
        }

        return $query->orderBy('name');
    }

    public function getNearby(float $latitude, float $longitude, float $radius = 10, ?int $governorateId = null): array
    {
        $query = Laboratory::with(['governorate', 'activeSubscription'])
            ->where('status', 'approved')
            ->whereHas('activeSubscription')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($governorateId) {
            $query->where('governorate_id', $governorateId);
        }

        $labs = $query->get();

        return $labs->map(function (Laboratory $lab) use ($latitude, $longitude) {
            $distance = $this->haversineKm($latitude, $longitude, (float) $lab->latitude, (float) $lab->longitude);

            return ['laboratory' => $lab, 'distance_km' => round($distance, 2)];
        })
            ->filter(fn ($row) => $row['distance_km'] <= $radius)
            ->sortBy('distance_km')
            ->values()
            ->all();
    }

    public function getProfile(int $id): ?Laboratory
    {
        return Laboratory::with([
            'governorate',
            'branches.governorate',
            'activeSubscription',
            'testItems' => fn ($q) => $q->available()->with('labTest.category'),
        ])
            ->where('status', 'approved')
            ->whereHas('activeSubscription')
            ->find($id);
    }

    public function getTestCatalog(int $laboratoryId, ?int $categoryId = null)
    {
        $query = LaboratoryTestItem::with(['labTest.category'])
            ->where('laboratory_id', $laboratoryId)
            ->available();

        if ($categoryId) {
            $query->whereHas('labTest', fn ($q) => $q->where('lab_test_category_id', $categoryId));
        }

        return $query->orderBy('price')->get();
    }

    public function formatLaboratory(Laboratory $lab, bool $detailed = false, ?float $distanceKm = null): array
    {
        $data = [
            'id' => $lab->id,
            'name' => $lab->name,
            'governorate_id' => $lab->governorate_id,
            'governorate_name' => $lab->governorate?->name_ar,
            'district' => $lab->district,
            'address' => $lab->address,
            'latitude' => $lab->latitude,
            'longitude' => $lab->longitude,
            'logo' => storage_public_url($lab->logo),
            'home_collection_enabled' => $lab->home_collection_enabled,
            'home_collection_fee' => $lab->home_collection_fee,
            'tests_count' => $lab->relationLoaded('testItems')
                ? $lab->testItems->where('is_available', true)->count()
                : $lab->testItems()->available()->count(),
            'rating' => (float) ($lab->rating ?? 0),
            'rating_count' => (int) ($lab->rating_count ?? 0),
        ];

        if ($distanceKm !== null) {
            $data['distance_km'] = $distanceKm;
        }

        if ($detailed) {
            $data += [
                'description_ar' => $lab->description_ar,
                'contact_phone' => $lab->contact_phone,
                'whatsapp' => $lab->whatsapp,
                'working_hours' => $lab->working_hours,
                'branches' => $lab->relationLoaded('branches')
                    ? $lab->branches->map(fn ($b) => [
                        'id' => $b->id,
                        'branch_name' => $b->branch_name,
                        'governorate_name' => $b->governorate?->name_ar,
                        'district' => $b->district,
                        'address' => $b->address,
                        'phone' => $b->phone,
                        'is_primary' => $b->is_primary,
                        'working_hours' => $b->working_hours,
                    ])->values()->all()
                    : [],
            ];
        }

        return $data;
    }

    public function formatTestItem(LaboratoryTestItem $item): array
    {
        $test = $item->labTest;

        return [
            'id' => $item->id,
            'lab_test_id' => $item->lab_test_id,
            'name_ar' => $test?->name_ar,
            'name_en' => $test?->name_en,
            'code' => $test?->code,
            'category_name' => $test?->category?->name_ar,
            'sample_type' => $test?->sample_type,
            'description_ar' => $test?->description_ar,
            'price' => $item->price,
            'result_hours' => $item->result_hours,
        ];
    }

    protected function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
