<?php

namespace Modules\Doctor\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Doctor\Http\Requests\Api\SearchDoctorsRequest;
use Modules\Doctor\Services\Api\DoctorService;
use App\Traits\ApiResponse;

class DoctorController extends Controller
{
    use ApiResponse;

    public function __construct(private DoctorService $doctorService)
    {
    }

    public function index(SearchDoctorsRequest $request): JsonResponse
    {
        $query = $this->doctorService->search($request->validated());

        $limit = $request->limit ?? 20;
        $doctors = $query->paginate($limit);

        return $this->paginated(
            collect($doctors->items())->map(fn ($doctor) => $this->formatDoctor($doctor))->all(),
            $doctors->total(),
            $doctors->currentPage(),
            $limit
        );
    }

    public function featured(SearchDoctorsRequest $request): JsonResponse
    {
        $query = $this->doctorService->getFeatured($request->validated());
        $limit = $request->limit ?? 20;
        $doctors = $query->paginate($limit);

        return $this->paginated(
            collect($doctors->items())->map(fn ($doctor) => $this->formatDoctor($doctor, true))->all(),
            $doctors->total(),
            $doctors->currentPage(),
            $limit
        );
    }

    public function nearby(): JsonResponse
    {
        $latitude = request('latitude');
        $longitude = request('longitude');
        $radius = (float) request('radius', 10);
        $governorate = request('governorate');
        $limit = (int) request('limit', 20);

        if (!$latitude || !$longitude) {
            return $this->error('الموقع الجغرافي مطلوب', 'LOCATION_REQUIRED', 400);
        }

        $results = $this->doctorService->getNearby(
            (float) $latitude,
            (float) $longitude,
            $radius,
            $governorate
        );

        $items = collect($results)
            ->take($limit)
            ->map(fn ($row) => $this->formatDoctor($row['doctor'], false, $row['distance_km']))
            ->values()
            ->all();

        return $this->success($items);
    }

    public function show(string $id): JsonResponse
    {
        $doctor = $this->doctorService->getProfile($id);

        if (!$doctor) {
            return $this->notFound('الطبيب غير موجود');
        }

        return $this->success($this->formatDoctor($doctor, true) + [
            'schedules' => $doctor->schedules->map(function ($schedule) {
                return [
                    'day_of_week' => $schedule->day_of_week,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                ];
            }),
        ]);
    }

    public function specialities(): JsonResponse
    {
        $specialities = $this->doctorService->getSpecialities();

        return $this->success($specialities->map(function ($speciality) {
            return [
                'id' => $speciality->id,
                'name_ar' => $speciality->name_ar,
                'name_en' => $speciality->name_en,
                'icon' => $speciality->icon,
            ];
        }));
    }

    public function schedule(string $id): JsonResponse
    {
        $doctor = $this->doctorService->getDoctorSchedule($id);

        if (!$doctor) {
            return $this->notFound('الطبيب غير موجود');
        }

        return $this->success([
            'doctor_id' => $doctor->id,
            'schedules' => $doctor->schedules->map(function ($schedule) {
                return [
                    'day_of_week' => $schedule->day_of_week,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                ];
            }),
        ]);
    }

    private function formatDoctor($doctor, bool $includePlan = false, ?float $distanceKm = null): array
    {
        $activeSub = $includePlan ? $doctor->activeSubscription() : null;

        $data = [
            'id' => $doctor->id,
            'name' => $doctor->user?->name,
            'speciality' => [
                'id' => $doctor->speciality?->id,
                'name_ar' => $doctor->speciality?->name_ar,
                'name_en' => $doctor->speciality?->name_en,
            ],
            'bio' => $doctor->bio_ar,
            'experience_years' => $doctor->experience_years,
            'consultation_fee' => $doctor->consultation_fee,
            'rating' => $doctor->rating,
            'rating_count' => $doctor->rating_count,
            'avatar' => storage_public_url($doctor->user?->avatar),
            'address' => $doctor->primaryBranch?->address ?? $doctor->address,
            'latitude' => $doctor->primaryBranch?->latitude ?? $doctor->latitude,
            'longitude' => $doctor->primaryBranch?->longitude ?? $doctor->longitude,
            'is_featured' => $doctor->hasFeaturedSubscription(),
        ];

        if ($includePlan && $activeSub?->subscription) {
            $data['plan_name'] = $activeSub->subscription->name;
        }

        if ($distanceKm !== null) {
            $data['distance_km'] = $distanceKm;
        }

        return $data;
    }
}
