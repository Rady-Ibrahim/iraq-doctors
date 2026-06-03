<?php

namespace Modules\Doctor\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Doctor\Http\Requests\Api\CreateBranchRequest;
use Modules\Doctor\Services\Api\DoctorBranchService;
use App\Traits\ApiResponse;

class DoctorBranchController extends Controller
{
    use ApiResponse;

    public function __construct(private DoctorBranchService $branchService)
    {
    }

    public function index(string $doctorId): JsonResponse
    {
        $branches = $this->branchService->getBranches($doctorId);

        return $this->success($branches->map(function ($branch) {
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
        }));
    }

    public function store(string $doctorId, CreateBranchRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user->isDoctor()) {
            return $this->forbidden('فقط الأطباء يمكنهم إضافة فروع', 'NOT_DOCTOR');
        }

        $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $user->id)->first();
        if (!$doctor || $doctor->id !== $doctorId) {
            return $this->forbidden('غير مصرح');
        }

        $branch = $this->branchService->create($doctorId, $request->validated());

        return $this->created([
            'id' => $branch->id,
            'branch_name' => $branch->branch_name,
            'governorate' => $branch->governorate,
            'district' => $branch->district,
            'address' => $branch->address,
            'latitude' => $branch->latitude,
            'longitude' => $branch->longitude,
            'phone' => $branch->phone,
            'is_primary' => $branch->is_primary,
        ], 'تم إضافة الفرع بنجاح');
    }

    public function show(string $branchId): JsonResponse
    {
        $branch = $this->branchService->getBranch($branchId);

        if (!$branch) {
            return $this->notFound('الفرع غير موجود', 'BRANCH_NOT_FOUND');
        }

        return $this->success([
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
        ]);
    }

    public function update(string $branchId, CreateBranchRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user->isDoctor()) {
            return $this->forbidden('فقط الأطباء يمكنهم تعديل الفروع', 'NOT_DOCTOR');
        }

        $branch = $this->branchService->getBranch($branchId);
        if (!$branch) {
            return $this->notFound('الفرع غير موجود', 'BRANCH_NOT_FOUND');
        }

        $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $user->id)->first();
        if (!$doctor || $branch->doctor_id !== $doctor->id) {
            return $this->forbidden('غير مصرح');
        }

        $updatedBranch = $this->branchService->update($branchId, $request->validated());

        return $this->success([
            'id' => $updatedBranch->id,
            'branch_name' => $updatedBranch->branch_name,
            'governorate' => $updatedBranch->governorate,
            'district' => $updatedBranch->district,
            'address' => $updatedBranch->address,
            'latitude' => $updatedBranch->latitude,
            'longitude' => $updatedBranch->longitude,
            'phone' => $updatedBranch->phone,
            'is_primary' => $updatedBranch->is_primary,
        ], 'تم تعديل الفرع بنجاح');
    }

    public function destroy(string $branchId): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user->isDoctor()) {
            return $this->forbidden('فقط الأطباء يمكنهم حذف الفروع', 'NOT_DOCTOR');
        }

        $branch = \Modules\Doctor\Models\DoctorBranch::findOrFail($branchId);
        $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $user->id)->first();
        if (!$doctor || $branch->doctor_id !== $doctor->id) {
            return $this->forbidden('غير مصرح');
        }

        $deleted = $this->branchService->delete($branchId);

        if (!$deleted) {
            return $this->error('لا يمكن حذف الفرع الرئيسي', 'CANNOT_DELETE_PRIMARY_BRANCH', 400);
        }

        return $this->success(null, 'تم حذف الفرع بنجاح');
    }

    public function nearby(): JsonResponse
    {
        $latitude = request('latitude');
        $longitude = request('longitude');
        $radius = request('radius', 10);
        $governorate = request('governorate');

        if (!$latitude || !$longitude) {
            return $this->error('الموقع الجغرافي مطلوب', 'LOCATION_REQUIRED', 400);
        }

        $branches = $this->branchService->searchNearbyBranches(
            $latitude,
            $longitude,
            $radius,
            $governorate
        );

        return $this->success($branches->map(function ($branch) {
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
        }));
    }
}
