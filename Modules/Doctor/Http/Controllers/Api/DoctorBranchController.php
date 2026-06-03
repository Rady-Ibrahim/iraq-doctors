<?php

namespace Modules\Doctor\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Doctor\Http\Requests\Api\CreateBranchRequest;
use Modules\Doctor\Services\Api\DoctorBranchService;

class DoctorBranchController extends Controller
{
    public function __construct(private DoctorBranchService $branchService)
    {
    }

    public function index(string $doctorId): JsonResponse
    {
        $branches = $this->branchService->getBranches($doctorId);

        return response()->json([
            'success' => true,
            'data' => $branches->map(function ($branch) {
                return [
                    'id' => $branch->id,
                    'branch_name' => $branch->branch_name,
                    'governorate' => $branch->governorate,
                    'district' => $branch->district,
                    'address' => $branch->address,
                    'latitude' => $branch->latitude,
                    'longitude' => $branch->longitude,
                    'phone' => $branch->phone,
                    'is_primary' => $branch->is_primary,
                ];
            }),
        ], 200);
    }

    public function store(string $doctorId, CreateBranchRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user->isDoctor()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_DOCTOR',
                    'message' => 'فقط الأطباء يمكنهم إضافة فروع',
                ],
            ], 403);
        }

        $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $user->id)->first();
        if (!$doctor || $doctor->id !== $doctorId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'غير مصرح',
                ],
            ], 403);
        }

        $branch = $this->branchService->create($doctorId, $request->validated());

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $branch->id,
                'branch_name' => $branch->branch_name,
                'governorate' => $branch->governorate,
                'district' => $branch->district,
                'address' => $branch->address,
                'latitude' => $branch->latitude,
                'longitude' => $branch->longitude,
                'phone' => $branch->phone,
                'is_primary' => $branch->is_primary,
            ],
            'message' => 'تم إضافة الفرع بنجاح',
        ], 201);
    }

    public function show(string $branchId): JsonResponse
    {
        $branch = $this->branchService->getBranch($branchId);

        if (!$branch) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'BRANCH_NOT_FOUND',
                    'message' => 'الفرع غير موجود',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $branch->id,
                'branch_name' => $branch->branch_name,
                'governorate' => $branch->governorate,
                'district' => $branch->district,
                'address' => $branch->address,
                'latitude' => $branch->latitude,
                'longitude' => $branch->longitude,
                'phone' => $branch->phone,
                'is_primary' => $branch->is_primary,
                'schedules' => $branch->schedules->map(function ($schedule) {
                    return [
                        'day_of_week' => $schedule->day_of_week,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                    ];
                }),
            ],
        ], 200);
    }

    public function update(string $branchId, CreateBranchRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user->isDoctor()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_DOCTOR',
                    'message' => 'فقط الأطباء يمكنهم تعديل الفروع',
                ],
            ], 403);
        }

        $branch = $this->branchService->getBranch($branchId);
        if (!$branch) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'BRANCH_NOT_FOUND',
                    'message' => 'الفرع غير موجود',
                ],
            ], 404);
        }

        $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $user->id)->first();
        if (!$doctor || $branch->doctor_id !== $doctor->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'غير مصرح',
                ],
            ], 403);
        }

        $updatedBranch = $this->branchService->update($branchId, $request->validated());

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $updatedBranch->id,
                'branch_name' => $updatedBranch->branch_name,
                'governorate' => $updatedBranch->governorate,
                'district' => $updatedBranch->district,
                'address' => $updatedBranch->address,
                'latitude' => $updatedBranch->latitude,
                'longitude' => $updatedBranch->longitude,
                'phone' => $updatedBranch->phone,
                'is_primary' => $updatedBranch->is_primary,
            ],
            'message' => 'تم تعديل الفرع بنجاح',
        ], 200);
    }

    public function destroy(string $branchId): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user->isDoctor()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_DOCTOR',
                    'message' => 'فقط الأطباء يمكنهم حذف الفروع',
                ],
            ], 403);
        }

        $branch = \Modules\Doctor\Models\DoctorBranch::findOrFail($branchId);
        $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $user->id)->first();
        if (!$doctor || $branch->doctor_id !== $doctor->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'غير مصرح',
                ],
            ], 403);
        }

        $deleted = $this->branchService->delete($branchId);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CANNOT_DELETE_PRIMARY_BRANCH',
                    'message' => 'لا يمكن حذف الفرع الرئيسي',
                ],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الفرع بنجاح',
        ], 200);
    }

    public function nearby(): JsonResponse
    {
        $latitude = request('latitude');
        $longitude = request('longitude');
        $radius = request('radius', 10);
        $governorate = request('governorate');

        if (!$latitude || !$longitude) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'LOCATION_REQUIRED',
                    'message' => 'الموقع الجغرافي مطلوب',
                ],
            ], 400);
        }

        $branches = $this->branchService->searchNearbyBranches(
            $latitude,
            $longitude,
            $radius,
            $governorate
        );

        return response()->json([
            'success' => true,
            'data' => $branches->map(function ($branch) {
                return [
                    'id' => $branch->id,
                    'doctor_id' => $branch->doctor_id,
                    'doctor_name' => $branch->doctor->user->name,
                    'speciality' => $branch->doctor->speciality->name_ar,
                    'branch_name' => $branch->branch_name,
                    'governorate' => $branch->governorate,
                    'district' => $branch->district,
                    'address' => $branch->address,
                    'latitude' => $branch->latitude,
                    'longitude' => $branch->longitude,
                    'phone' => $branch->phone,
                    'rating' => $branch->doctor->rating,
                    'consultation_fee' => $branch->doctor->consultation_fee,
                ];
            }),
        ], 200);
    }
}
