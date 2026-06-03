<?php

namespace Modules\Doctor\Services\Api;

use Modules\Doctor\Models\DoctorBranch;
use Modules\Doctor\Models\Doctor;
use Illuminate\Support\Facades\DB;

class DoctorBranchService
{
    public function create(string $doctorId, array $data): DoctorBranch
    {
        return DB::transaction(function () use ($doctorId, $data) {
            if ($data['is_primary'] ?? false) {
                DoctorBranch::where('doctor_id', $doctorId)->update(['is_primary' => false]);
            }

            return DoctorBranch::create([
                'doctor_id' => $doctorId,
                'branch_name' => $data['branch_name'],
                'governorate' => $data['governorate'] ?? null,
                'district' => $data['district'] ?? null,
                'address' => $data['address'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'phone' => $data['phone'] ?? null,
                'is_primary' => $data['is_primary'] ?? false,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    public function update(string $branchId, array $data): ?DoctorBranch
    {
        $branch = DoctorBranch::findOrFail($branchId);

        if ($data['is_primary'] ?? false) {
            DoctorBranch::where('doctor_id', $branch->doctor_id)
                ->where('id', '!=', $branchId)
                ->update(['is_primary' => false]);
        }

        $branch->update($data);

        return $branch;
    }

    public function delete(string $branchId): bool
    {
        $branch = DoctorBranch::findOrFail($branchId);

        if ($branch->is_primary) {
            return false;
        }

        return $branch->delete();
    }

    public function getBranches(string $doctorId)
    {
        return DoctorBranch::where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->orderBy('is_primary', 'desc')
            ->get();
    }

    public function getBranch(string $branchId): ?DoctorBranch
    {
        return DoctorBranch::with('schedules')
            ->where('id', $branchId)
            ->where('is_active', true)
            ->first();
    }

    public function searchNearbyBranches(float $latitude, float $longitude, float $radius, string $governorate = null)
    {
        $query = DoctorBranch::with(['doctor.user', 'doctor.speciality'])
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($governorate) {
            $query->where('governorate', $governorate);
        }

        $query->whereRaw("
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
            cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) < ?
        ", [$latitude, $longitude, $latitude, $radius]);

        return $query->orderByRaw("
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
            cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))
        ", [$latitude, $longitude, $latitude])
            ->get();
    }
}
