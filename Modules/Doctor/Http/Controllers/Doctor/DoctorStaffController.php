<?php

namespace Modules\Doctor\Http\Controllers\Doctor;

use App\Support\DoctorDashboardContext;
use App\Traits\ApiResponse;
use App\Traits\ResolvesDoctorDashboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Doctor\Http\Requests\Web\StoreDoctorStaffRequest;
use Modules\Doctor\Http\Requests\Web\UpdateDoctorStaffRequest;
use Modules\Doctor\Services\DoctorStaffService;

class DoctorStaffController extends Controller
{
    use ApiResponse;
    use ResolvesDoctorDashboard;

    public function __construct(private DoctorStaffService $doctorStaffService) {}

    public function permissionsCatalog(): JsonResponse
    {
        return $this->success($this->doctorStaffService->permissionCatalog());
    }

    public function me(): JsonResponse
    {
        $context = DoctorDashboardContext::resolve();

        return $this->success([
            'user' => [
                'id' => $context->user->id,
                'name' => $context->user->name,
                'phone' => $context->user->phone,
                'email' => $context->user->email,
                'role' => $context->user->role,
            ],
            'is_owner' => $context->isOwner(),
            'is_staff' => $context->isStaff(),
            'permissions' => $context->permissions(),
            'doctor' => [
                'id' => $context->doctor->id,
                'name' => $context->doctor->user?->name,
            ],
        ]);
    }

    public function index(): JsonResponse
    {
        $doctor = $this->resolveDoctor();

        return $this->success($this->doctorStaffService->listForDoctor($doctor->id));
    }

    public function store(StoreDoctorStaffRequest $request): JsonResponse
    {
        $doctor = $this->resolveDoctor();
        $member = $this->doctorStaffService->createStaff($doctor, $request->validated());

        return $this->created([
            'id' => $member->id,
            'status' => $member->status,
            'permissions' => $member->permissions,
            'user' => [
                'id' => $member->user->id,
                'name' => $member->user->name,
                'phone' => $member->user->phone,
                'email' => $member->user->email,
            ],
        ], 'تم إضافة السكرتير بنجاح');
    }

    public function update(UpdateDoctorStaffRequest $request, int $memberId): JsonResponse
    {
        $doctor = $this->resolveDoctor();
        $member = $this->doctorStaffService->updateStaff($doctor, $memberId, $request->validated());

        return $this->success([
            'id' => $member->id,
            'status' => $member->status,
            'permissions' => $member->permissions,
            'user' => [
                'id' => $member->user->id,
                'name' => $member->user->name,
                'phone' => $member->user->phone,
                'email' => $member->user->email,
            ],
        ], 'تم تحديث بيانات السكرتير بنجاح');
    }

    public function updateStatus(Request $request, int $memberId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $doctor = $this->resolveDoctor();
        $member = $this->doctorStaffService->updateStatus(
            $doctor,
            $memberId,
            $request->input('status')
        );

        return $this->success([
            'id' => $member->id,
            'status' => $member->status,
        ], $member->status === 'active' ? 'تم تفعيل السكرتير' : 'تم تعطيل السكرتير');
    }

    public function destroy(int $memberId): JsonResponse
    {
        $doctor = $this->resolveDoctor();
        $this->doctorStaffService->deleteStaff($doctor, $memberId);

        return $this->success(null, 'تم حذف السكرتير بنجاح');
    }
}
