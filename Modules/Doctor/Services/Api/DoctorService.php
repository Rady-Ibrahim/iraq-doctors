<?php

namespace Modules\Doctor\Services\Api;

use Modules\Doctor\Models\Doctor;
use Modules\Doctor\Models\Speciality;
use Illuminate\Database\Eloquent\Builder;

class DoctorService
{
    public function search(array $filters): Builder
    {
        $query = Doctor::with(['user', 'speciality'])
            ->where('status', 'approved');

        if (isset($filters['speciality_id'])) {
            $query->where('speciality_id', $filters['speciality_id']);
        }

        if (isset($filters['name'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['name'] . '%');
            });
        }

        if (isset($filters['min_rating'])) {
            $query->where('rating', '>=', $filters['min_rating']);
        }

        if (isset($filters['max_fee'])) {
            $query->where('consultation_fee', '<=', $filters['max_fee']);
        }

        if (isset($filters['latitude']) && isset($filters['longitude']) && isset($filters['radius'])) {
            $query->whereRaw("
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) < ?
            ", [
                $filters['latitude'],
                $filters['longitude'],
                $filters['latitude'],
                $filters['radius']
            ]);
        }

        if (isset($filters['sort_by'])) {
            switch ($filters['sort_by']) {
                case 'rating':
                    $query->orderBy('rating', 'desc');
                    break;
                case 'fee_asc':
                    $query->orderBy('consultation_fee', 'asc');
                    break;
                case 'fee_desc':
                    $query->orderBy('consultation_fee', 'desc');
                    break;
                case 'experience':
                    $query->orderBy('experience_years', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('rating', 'desc');
        }

        return $query;
    }

    public function getProfile(string $doctorId): ?Doctor
    {
        return Doctor::with(['user', 'speciality', 'schedules', 'reviews'])
            ->where('id', $doctorId)
            ->where('status', 'approved')
            ->first();
    }

    public function getSpecialities()
    {
        return Speciality::where('is_active', true)
            ->orderBy('name_ar')
            ->get();
    }

    public function getDoctorSchedule(string $doctorId)
    {
        return Doctor::with(['schedules' => function ($query) {
            $query->where('is_active', true);
        }])
            ->where('id', $doctorId)
            ->where('status', 'approved')
            ->first();
    }
}
