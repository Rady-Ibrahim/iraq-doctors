<?php

namespace Modules\Pharmacy\Http\Controllers\Web\Data;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pharmacy\Http\Requests\Web\CreatePharmacyBranchRequest;
use Modules\Pharmacy\Http\Requests\Web\UpdatePharmacyProfileRequest;
use Modules\Pharmacy\Models\Pharmacy;
use Modules\Pharmacy\Services\Web\PharmacyBranchService;
use Modules\Pharmacy\Services\Web\PharmacyProfileService;

class PharmacyProfileDataController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PharmacyProfileService $profileService,
        private PharmacyBranchService $branchService,
    ) {}

    protected function resolvePharmacy(): Pharmacy
    {
        return Pharmacy::where('user_id', auth('web')->id())->firstOrFail();
    }

    public function show(): JsonResponse
    {
        $pharmacy = $this->resolvePharmacy();

        return $this->success($this->profileService->getProfile($pharmacy));
    }

    public function update(UpdatePharmacyProfileRequest $request): JsonResponse
    {
        $pharmacy = $this->resolvePharmacy();
        $updated = $this->profileService->updateProfile(
            $pharmacy,
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
        $pharmacy = $this->resolvePharmacy();
        $branches = $this->branchService->getBranches($pharmacy->id);

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

    public function storeBranch(CreatePharmacyBranchRequest $request): JsonResponse
    {
        $pharmacy = $this->resolvePharmacy();
        $branch = $this->branchService->create($pharmacy->id, $request->validated());

        return $this->created([
            'id' => $branch->id,
            'branch_name' => $branch->branch_name,
            'is_primary' => $branch->is_primary,
        ], 'تم إضافة الفرع بنجاح');
    }

    public function updateBranch(string $branchId, CreatePharmacyBranchRequest $request): JsonResponse
    {
        $pharmacy = $this->resolvePharmacy();
        $branch = $this->branchService->update((int) $branchId, $pharmacy->id, $request->validated());

        return $this->success([
            'id' => $branch->id,
            'branch_name' => $branch->branch_name,
            'is_primary' => $branch->is_primary,
        ], 'تم تعديل الفرع بنجاح');
    }

    public function destroyBranch(string $branchId): JsonResponse
    {
        $pharmacy = $this->resolvePharmacy();
        $deleted = $this->branchService->delete((int) $branchId, $pharmacy->id);

        if (! $deleted) {
            return $this->error('لا يمكن حذف الفرع الرئيسي', 'CANNOT_DELETE_PRIMARY_BRANCH', 400);
        }

        return $this->success(null, 'تم حذف الفرع بنجاح');
    }
}
