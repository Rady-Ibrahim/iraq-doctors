<?php

namespace Modules\Pharmacy\Services\Api;

use Illuminate\Database\Eloquent\Builder;
use Modules\Pharmacy\Models\Pharmacy;
use Modules\Pharmacy\Models\PharmacyMedicine;

class PharmacyService
{
    public function search(array $filters): Builder
    {
        $query = Pharmacy::with(['governorate', 'activeSubscription'])
            ->where('status', 'approved')
            ->whereHas('activeSubscription');

        if (! empty($filters['governorate_id'])) {
            $query->where('governorate_id', $filters['governorate_id']);
        }

        if (! empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (! empty($filters['delivery'])) {
            $query->where('delivery_enabled', true);
        }

        return $query->orderBy('name');
    }

    public function getNearby(float $latitude, float $longitude, float $radius = 10, ?int $governorateId = null): array
    {
        $query = Pharmacy::with(['governorate', 'activeSubscription'])
            ->where('status', 'approved')
            ->whereHas('activeSubscription')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($governorateId) {
            $query->where('governorate_id', $governorateId);
        }

        $pharmacies = $query->get();

        return $pharmacies->map(function (Pharmacy $pharmacy) use ($latitude, $longitude) {
            $distance = $this->haversineKm($latitude, $longitude, (float) $pharmacy->latitude, (float) $pharmacy->longitude);

            return ['pharmacy' => $pharmacy, 'distance_km' => round($distance, 2)];
        })
            ->filter(fn ($row) => $row['distance_km'] <= $radius)
            ->sortBy('distance_km')
            ->values()
            ->all();
    }

    public function getProfile(int $id): ?Pharmacy
    {
        return Pharmacy::with([
            'governorate',
            'branches.governorate',
            'activeSubscription',
            'pharmacyMedicines' => fn ($q) => $q->available()->with('medicine.category'),
        ])
            ->where('status', 'approved')
            ->whereHas('activeSubscription')
            ->find($id);
    }

    public function getMedicineCatalog(int $pharmacyId, ?int $categoryId = null)
    {
        $query = PharmacyMedicine::with(['medicine.category'])
            ->where('pharmacy_id', $pharmacyId)
            ->available()
            ->where('stock_quantity', '>', 0);

        if ($categoryId) {
            $query->whereHas('medicine', fn ($q) => $q->where('medicine_category_id', $categoryId));
        }

        return $query->orderBy('price')->get();
    }

    public function formatPharmacy(Pharmacy $pharmacy, bool $detailed = false, ?float $distanceKm = null): array
    {
        $data = [
            'id' => $pharmacy->id,
            'name' => $pharmacy->name,
            'governorate_id' => $pharmacy->governorate_id,
            'governorate_name' => $pharmacy->governorate?->name_ar,
            'district' => $pharmacy->district,
            'address' => $pharmacy->address,
            'latitude' => $pharmacy->latitude,
            'longitude' => $pharmacy->longitude,
            'logo' => storage_public_url($pharmacy->logo),
            'delivery_enabled' => $pharmacy->delivery_enabled,
            'delivery_fee' => $pharmacy->delivery_fee,
            'min_order_for_delivery' => $pharmacy->min_order_for_delivery,
            'medicines_count' => $pharmacy->relationLoaded('pharmacyMedicines')
                ? $pharmacy->pharmacyMedicines->where('is_available', true)->where('stock_quantity', '>', 0)->count()
                : $pharmacy->pharmacyMedicines()->available()->where('stock_quantity', '>', 0)->count(),
            'rating' => (float) ($pharmacy->rating ?? 0),
            'rating_count' => (int) ($pharmacy->rating_count ?? 0),
        ];

        if ($distanceKm !== null) {
            $data['distance_km'] = $distanceKm;
        }

        if ($detailed) {
            $data += [
                'description_ar' => $pharmacy->description_ar,
                'contact_phone' => $pharmacy->contact_phone,
                'whatsapp' => $pharmacy->whatsapp,
                'working_hours' => $pharmacy->working_hours,
                'branches' => $pharmacy->relationLoaded('branches')
                    ? $pharmacy->branches->map(fn ($b) => [
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

    public function formatMedicineItem(PharmacyMedicine $item): array
    {
        $medicine = $item->medicine;

        return [
            'id' => $item->id,
            'medicine_id' => $item->medicine_id,
            'name_ar' => $medicine?->name_ar,
            'name_en' => $medicine?->name_en,
            'generic_name' => $medicine?->generic_name,
            'barcode' => $medicine?->barcode,
            'category_name' => $medicine?->category?->name_ar,
            'dosage_form' => $medicine?->dosage_form,
            'strength' => $medicine?->strength,
            'manufacturer' => $medicine?->manufacturer,
            'description_ar' => $medicine?->description_ar,
            'price' => $item->price,
            'stock_quantity' => $item->stock_quantity,
            'expiry_date' => $item->expiry_date?->format('Y-m-d'),
            'is_expiring_soon' => $item->expiry_date
                && $item->expiry_date->isFuture()
                && $item->expiry_date->lte(now()->addDays(30)),
        ];
    }

    public function compareMedicinePrices(int $medicineId, ?int $governorateId = null, ?float $latitude = null, ?float $longitude = null): array
    {
        $items = PharmacyMedicine::with(['pharmacy.governorate', 'medicine'])
            ->where('medicine_id', $medicineId)
            ->available()
            ->where('stock_quantity', '>', 0)
            ->whereHas('pharmacy', function ($q) use ($governorateId) {
                $q->where('status', 'approved')->whereHas('activeSubscription');
                if ($governorateId) {
                    $q->where('governorate_id', $governorateId);
                }
            })
            ->get();

        return $items->map(function (PharmacyMedicine $item) use ($latitude, $longitude) {
            $pharmacy = $item->pharmacy;
            $distance = null;

            if ($latitude !== null && $longitude !== null && $pharmacy->latitude && $pharmacy->longitude) {
                $distance = round($this->haversineKm(
                    $latitude,
                    $longitude,
                    (float) $pharmacy->latitude,
                    (float) $pharmacy->longitude
                ), 2);
            }

            return [
                'pharmacy_id' => $pharmacy->id,
                'pharmacy_name' => $pharmacy->name,
                'governorate_name' => $pharmacy->governorate?->name_ar,
                'district' => $pharmacy->district,
                'price' => (float) $item->price,
                'stock_quantity' => (int) $item->stock_quantity,
                'rating' => (float) ($pharmacy->rating ?? 0),
                'rating_count' => (int) ($pharmacy->rating_count ?? 0),
                'delivery_enabled' => (bool) $pharmacy->delivery_enabled,
                'delivery_fee' => $pharmacy->delivery_fee,
                'distance_km' => $distance,
            ];
        })
            ->sortBy('price')
            ->values()
            ->all();
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
