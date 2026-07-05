<?php

namespace Modules\Laboratory\Services\Web;

use Illuminate\Support\Facades\DB;
use Modules\Laboratory\Models\LaboratoryBranch;

class LaboratoryBranchService
{
    public function getBranches(int $laboratoryId)
    {
        return LaboratoryBranch::with('governorate')
            ->where('laboratory_id', $laboratoryId)
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->get();
    }

    public function create(int $laboratoryId, array $data): LaboratoryBranch
    {
        return DB::transaction(function () use ($laboratoryId, $data) {
            if ($data['is_primary'] ?? false) {
                LaboratoryBranch::where('laboratory_id', $laboratoryId)->update(['is_primary' => false]);
            }

            return LaboratoryBranch::create([
                'laboratory_id' => $laboratoryId,
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

    public function update(int $branchId, int $laboratoryId, array $data): LaboratoryBranch
    {
        return DB::transaction(function () use ($branchId, $laboratoryId, $data) {
            $branch = LaboratoryBranch::where('laboratory_id', $laboratoryId)->findOrFail($branchId);

            if ($data['is_primary'] ?? false) {
                LaboratoryBranch::where('laboratory_id', $laboratoryId)
                    ->where('id', '!=', $branchId)
                    ->update(['is_primary' => false]);
            }

            $branch->update($data);

            return $branch->fresh('governorate');
        });
    }

    public function delete(int $branchId, int $laboratoryId): bool
    {
        $branch = LaboratoryBranch::where('laboratory_id', $laboratoryId)->findOrFail($branchId);

        if ($branch->is_primary) {
            return false;
        }

        return (bool) $branch->delete();
    }
}
