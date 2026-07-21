<?php

namespace Modules\Doctor\Services\Api;

use Modules\Doctor\Models\Doctor;
use Modules\Doctor\Models\Speciality;
use Illuminate\Database\Eloquent\Builder;

class DoctorService
{
    public function search(array $filters): Builder
    {
        $query = Doctor::with(['user', 'speciality', 'primaryBranch.governorateModel'])
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

        if (isset($filters['max_rating'])) {
            $query->where('rating', '<=', $filters['max_rating']);
        }

        if (isset($filters['min_fee'])) {
            $query->where('consultation_fee', '>=', $filters['min_fee']);
        }

        if (isset($filters['max_fee'])) {
            $query->where('consultation_fee', '<=', $filters['max_fee']);
        }

        if (isset($filters['consultation_type'])) {
            $query->where('consultation_type', $filters['consultation_type']);
        }

        if (isset($filters['experience_level'])) {
            switch ($filters['experience_level']) {
                case 'junior':
                    $query->where('experience_years', '<', 1);
                    break;
                case 'intermediate':
                    $query->whereBetween('experience_years', [1, 5]);
                    break;
                case 'senior':
                    $query->where('experience_years', '>', 5);
                    break;
            }
        }

        if (isset($filters['governorate_id'])) {
            $query->whereHas('branches', function ($q) use ($filters) {
                $q->where('governorate_id', $filters['governorate_id']);
            });
        }

        if (isset($filters['availability'])) {
            $query = $this->filterByAvailability($query, $filters['availability']);
        }

        if (isset($filters['latitude']) && isset($filters['longitude'])) {
            $distanceRange = $filters['distance_range'] ?? 50;
            
            if (isset($filters['governorate'])) {
                $query->whereHas('branches', function ($q) use ($filters, $distanceRange) {
                    $q->whereRaw("
                        (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
                        cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) < ?
                    ", [
                        $filters['latitude'],
                        $filters['longitude'],
                        $filters['latitude'],
                        $distanceRange
                    ]);
                });
            } else {
                $query->whereRaw("
                    (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) < ?
                ", [
                    $filters['latitude'],
                    $filters['longitude'],
                    $filters['latitude'],
                    $distanceRange
                ]);
            }
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
                case 'distance':
                    if (isset($filters['latitude']) && isset($filters['longitude'])) {
                        $query->orderByRaw("
                            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
                            cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))
                        ", [
                            $filters['latitude'],
                            $filters['longitude'],
                            $filters['latitude']
                        ]);
                    }
                    break;
                default:
                    $query->orderBy('rating', 'desc');
            }
        } else {
            $query->orderBy('rating', 'desc');
        }

        return $query;
    }

    public function getFeatured(array $filters = []): Builder
    {
        $query = Doctor::with(['user', 'speciality', 'primaryBranch'])
            ->where('status', 'approved')
            ->whereHas('doctorSubscriptions', function ($q) {
                $q->active()
                    ->whereHas('subscription', function ($sq) {
                        $sq->where('is_featured', true)->where('status', 'active');
                    });
            });

        if (!empty($filters['speciality_id'])) {
            $query->where('speciality_id', $filters['speciality_id']);
        }

        if (!empty($filters['governorate_id'])) {
            $query->whereHas('branches', function ($q) use ($filters) {
                $q->where('governorate_id', $filters['governorate_id']);
            });
        }

        return $query->orderByDesc('rating');
    }

    public function getNearby(float $latitude, float $longitude, float $radius = 10, ?int $governorate = null): array
    {
        $branchQuery = \Modules\Doctor\Models\DoctorBranch::query()
            ->selectRaw("
                doctor_branches.*,
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))
                )) AS distance_km
            ", [$latitude, $longitude, $latitude])
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereHas('doctor', fn ($q) => $q->where('status', 'approved'));

        if ($governorate) {
            $branchQuery->where('governorate_id', $governorate);
        }

        $branchQuery->whereRaw("
            (6371 * acos(
                cos(radians(?)) * cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))
            )) < ?
        ", [$latitude, $longitude, $latitude, $radius])
            ->orderByRaw("
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))
                ))
            ", [$latitude, $longitude, $latitude]);

        $branches = $branchQuery->get();

        $doctorDistances = [];
        foreach ($branches as $branch) {
            $doctorId = $branch->doctor_id;
            if (!isset($doctorDistances[$doctorId]) || $branch->distance_km < $doctorDistances[$doctorId]) {
                $doctorDistances[$doctorId] = round((float) $branch->distance_km, 2);
            }
        }

        if (empty($doctorDistances)) {
            return [];
        }

        $doctors = Doctor::with(['user', 'speciality', 'primaryBranch'])
            ->whereIn('id', array_keys($doctorDistances))
            ->get()
            ->sortBy(fn ($doctor) => $doctorDistances[$doctor->id])
            ->values();

        return $doctors->map(fn ($doctor) => [
            'doctor' => $doctor,
            'distance_km' => $doctorDistances[$doctor->id],
        ])->all();
    }

    private function filterByAvailability(Builder $query, string $availability): Builder
    {
        $targetDate = match($availability) {
            'today' => now(),
            'tomorrow' => now()->addDay(),
            'this_week' => now(),
            default => now(),
        };

        $dayOfWeek = $targetDate->format('l');
        $dateStr = $targetDate->toDateString();

        $query->whereHas('schedules', function ($q) use ($dayOfWeek) {
            $q->where('day_of_week', $dayOfWeek)
              ->where('is_active', true);
        });

        $query->whereDoesntHave('appointments', function ($q) use ($dateStr) {
            $q->where('appointment_date', $dateStr)
              ->whereIn('status', ['pending', 'confirmed']);
        });

        if ($availability === 'this_week') {
            $endDate = now()->endOfWeek();
            $query->whereHas('schedules', function ($q) {
                $q->where('is_active', true);
            });
        }

        return $query;
    }

    public function getProfile(string $doctorId): ?Doctor
    {
        return Doctor::with(['user', 'speciality', 'schedules', 'approvedReviews'])
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
