<?php

namespace Modules\Pharmacy\Services\Web;

use Illuminate\Support\Facades\DB;
use Modules\Pharmacy\Models\PharmacyBranch;

class PharmacyBranchService
{
    public function getBranches(int $pharmacyId)
    {
        return PharmacyBranch::with('governorate')
            ->where('pharmacy_id', $pharmacyId)
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->get();
    }

    public function create(int $pharmacyId, array $data): PharmacyBranch
    {
        return DB::transaction(function () use ($pharmacyId, $data) {
            if ($data['is_primary'] ?? false) {
                PharmacyBranch::where('pharmacy_id', $pharmacyId)->update(['is_primary' => false]);
            }

            return PharmacyBranch::create([
                'pharmacy_id' => $pharmacyId,
                'governorate_id' => $data['governorate_id'] ?? null,
                'branch_name' => $data['branch_name'],
                'district' => $data['district'] ?? null,
                'address' => $data['address'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'phone' => $data['phone'] ?? null,
                'is_primary' => $data['is_primary'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'working_hours' => $data['working_hours'] ?? null,
            ]);
        });
    }

    public function update(int $branchId, int $pharmacyId, array $data): PharmacyBranch
    {
        return DB::transaction(function () use ($branchId, $pharmacyId, $data) {
            $branch = PharmacyBranch::where('pharmacy_id', $pharmacyId)->findOrFail($branchId);

            if ($data['is_primary'] ?? false) {
                PharmacyBranch::where('pharmacy_id', $pharmacyId)
                    ->where('id', '!=', $branchId)
                    ->update(['is_primary' => false]);
            }

            $branch->update($data);

            return $branch->fresh('governorate');
        });
    }

    public function delete(int $branchId, int $pharmacyId): bool
    {
        $branch = PharmacyBranch::where('pharmacy_id', $pharmacyId)->findOrFail($branchId);

        if ($branch->is_primary) {
            return false;
        }

        return (bool) $branch->delete();
    }
}
