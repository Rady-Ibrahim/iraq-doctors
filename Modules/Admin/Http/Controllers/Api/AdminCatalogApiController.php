<?php

namespace Modules\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Doctor\Models\Governorate;
use Modules\Doctor\Models\Speciality;
use App\Traits\ApiResponse;

class AdminCatalogApiController extends Controller
{
    use ApiResponse;

    public function specialities(): JsonResponse
    {
        $items = Speciality::orderBy('name_ar')->get();

        return $this->success($items);
    }

    public function storeSpeciality(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $speciality = Speciality::create([
            ...$validated,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return $this->created($speciality, 'تم إضافة التخصص بنجاح');
    }

    public function updateSpeciality(Request $request, int $id): JsonResponse
    {
        $speciality = Speciality::findOrFail($id);

        $validated = $request->validate([
            'name_ar' => 'sometimes|string|max:255',
            'name_en' => 'sometimes|string|max:255',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $speciality->update($validated);

        return $this->success($speciality, 'تم تحديث التخصص بنجاح');
    }

    public function destroySpeciality(int $id): JsonResponse
    {
        $speciality = Speciality::findOrFail($id);

        if ($speciality->doctors()->exists()) {
            return $this->error('لا يمكن حذف تخصص مرتبط بأطباء', 'CONFLICT', 409);
        }

        $speciality->delete();

        return $this->success(null, 'تم حذف التخصص بنجاح');
    }

    public function governorates(): JsonResponse
    {
        $items = Governorate::orderBy('name_ar')->get();

        return $this->success($items);
    }

    public function storeGovernorate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $governorate = Governorate::create([
            ...$validated,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return $this->created($governorate, 'تم إضافة المحافظة بنجاح');
    }

    public function updateGovernorate(Request $request, int $id): JsonResponse
    {
        $governorate = Governorate::findOrFail($id);

        $validated = $request->validate([
            'name_ar' => 'sometimes|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $governorate->update($validated);

        return $this->success($governorate, 'تم تحديث المحافظة بنجاح');
    }

    public function destroyGovernorate(int $id): JsonResponse
    {
        Governorate::findOrFail($id)->delete();

        return $this->success(null, 'تم حذف المحافظة بنجاح');
    }
}
