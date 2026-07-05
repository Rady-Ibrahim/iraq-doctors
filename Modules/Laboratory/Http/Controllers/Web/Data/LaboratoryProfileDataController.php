<?php

namespace Modules\Laboratory\Http\Controllers\Web\Data;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Laboratory\Http\Requests\Web\CreateLaboratoryBranchRequest;
use Modules\Laboratory\Http\Requests\Web\UpdateLaboratoryProfileRequest;
use Modules\Laboratory\Models\Laboratory;
use Modules\Laboratory\Services\Web\LaboratoryBranchService;
use Modules\Laboratory\Services\Web\LaboratoryProfileService;

class LaboratoryProfileDataController extends Controller
{
    use ApiResponse;

    public function __construct(
        private LaboratoryProfileService $profileService,
        private LaboratoryBranchService $branchService,
    ) {}

    protected function resolveLaboratory(): Laboratory
    {
        return Laboratory::where('user_id', auth('web')->id())->firstOrFail();
    }

    public function show(): JsonResponse
    {
        $laboratory = $this->resolveLaboratory();

        return $this->success($this->profileService->getProfile($laboratory));
    }

    public function update(UpdateLaboratoryProfileRequest $request): JsonResponse
    {
        $laboratory = $this->resolveLaboratory();
        $updated = $this->profileService->updateProfile(
            $laboratory,
            $request->validated(),
            $request->file('logo')
        );

        return $this->success(
            $this->profileService->getProfile($updated),
            'تم حفظ الإعدادات بنجاح'
        );
    }

    public function branches(): JsonResponse
    {
        $laboratory = $this->resolveLaboratory();
        $branches = $this->branchService->getBranches($laboratory->id);

        return $this->success($branches->map(fn ($branch) => [
            'id' => $branch->id,
            'branch_name' => $branch->branch_name,
            'governorate_id' => $branch->governorate_id,
            'governorate_name' => $branch->governorate?->name_ar,
            'district' => $branch->district,
            'address' => $branch->address,
            'latitude' => $branch->latitude,
            'longitude' => $branch->longitude,
            'phone' => $branch->phone,
            'is_primary' => $branch->is_primary,
            'working_hours' => $branch->working_hours,
        ]));
    }

    public function storeBranch(CreateLaboratoryBranchRequest $request): JsonResponse
    {
        $laboratory = $this->resolveLaboratory();
        $branch = $this->branchService->create($laboratory->id, $request->validated());

        return $this->created([
            'id' => $branch->id,
            'branch_name' => $branch->branch_name,
            'is_primary' => $branch->is_primary,
        ], 'تم إضافة الفرع بنجاح');
    }

    public function updateBranch(string $branchId, CreateLaboratoryBranchRequest $request): JsonResponse
    {
        $laboratory = $this->resolveLaboratory();
        $branch = $this->branchService->update((int) $branchId, $laboratory->id, $request->validated());

        return $this->success([
            'id' => $branch->id,
            'branch_name' => $branch->branch_name,
            'is_primary' => $branch->is_primary,
        ], 'تم تعديل الفرع بنجاح');
    }

    public function destroyBranch(string $branchId): JsonResponse
    {
        $laboratory = $this->resolveLaboratory();
        $deleted = $this->branchService->delete((int) $branchId, $laboratory->id);

        if (!$deleted) {
            return $this->error('لا يمكن حذف الفرع الرئيسي', 'CANNOT_DELETE_PRIMARY_BRANCH', 400);
        }

        return $this->success(null, 'تم حذف الفرع بنجاح');
    }
}
