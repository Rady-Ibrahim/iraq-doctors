<?php

namespace Modules\Doctor\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Doctor\Http\Requests\Api\SearchDoctorsRequest;
use Modules\Doctor\Services\Api\DoctorService;

class DoctorController extends Controller
{
    public function __construct(private DoctorService $doctorService)
    {
    }

    public function index(SearchDoctorsRequest $request): JsonResponse
    {
        $query = $this->doctorService->search($request->validated());

        $limit = $request->limit ?? 20;
        $doctors = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $doctors->items(),
            'meta' => [
                'page' => $doctors->currentPage(),
                'limit' => $limit,
                'total' => $doctors->total(),
                'last_page' => $doctors->lastPage(),
            ],
        ], 200);
    }

    public function show(string $id): JsonResponse
    {
        $doctor = $this->doctorService->getProfile($id);

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DOCTOR_NOT_FOUND',
                    'message' => 'الطبيب غير موجود',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $doctor->id,
                'name' => $doctor->user->name,
                'speciality' => [
                    'id' => $doctor->speciality->id,
                    'name_ar' => $doctor->speciality->name_ar,
                    'name_en' => $doctor->speciality->name_en,
                ],
                'bio' => $doctor->bio_ar,
                'experience_years' => $doctor->experience_years,
                'consultation_fee' => $doctor->consultation_fee,
                'rating' => $doctor->rating,
                'rating_count' => $doctor->rating_count,
                'address' => $doctor->address,
                'schedules' => $doctor->schedules->map(function ($schedule) {
                    return [
                        'day_of_week' => $schedule->day_of_week,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                    ];
                }),
            ],
        ], 200);
    }

    public function specialities(): JsonResponse
    {
        $specialities = $this->doctorService->getSpecialities();

        return response()->json([
            'success' => true,
            'data' => $specialities->map(function ($speciality) {
                return [
                    'id' => $speciality->id,
                    'name_ar' => $speciality->name_ar,
                    'name_en' => $speciality->name_en,
                    'icon' => $speciality->icon,
                ];
            }),
        ], 200);
    }

    public function schedule(string $id): JsonResponse
    {
        $doctor = $this->doctorService->getDoctorSchedule($id);

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DOCTOR_NOT_FOUND',
                    'message' => 'الطبيب غير موجود',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'doctor_id' => $doctor->id,
                'schedules' => $doctor->schedules->map(function ($schedule) {
                    return [
                        'day_of_week' => $schedule->day_of_week,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                    ];
                }),
            ],
        ], 200);
    }
}
