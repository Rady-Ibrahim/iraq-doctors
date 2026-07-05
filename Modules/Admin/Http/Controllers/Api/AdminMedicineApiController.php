<?php

namespace Modules\Admin\Http\Controllers\Api;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Http\Requests\Web\StoreMedicineCategoryRequest;
use Modules\Admin\Http\Requests\Web\StoreMedicineRequest;
use Modules\Pharmacy\Models\Medicine;
use Modules\Pharmacy\Models\MedicineCategory;

class AdminMedicineApiController extends Controller
{
    use ApiResponse;

    public function categories(): JsonResponse
    {
        $items = MedicineCategory::withCount('medicines')
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get()
            ->map(fn ($cat) => $this->formatCategory($cat));

        return $this->success($items);
    }

    public function storeCategory(StoreMedicineCategoryRequest $request): JsonResponse
    {
        $category = MedicineCategory::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->created($this->formatCategory($category->loadCount('medicines')), 'تم إضافة التصنيف بنجاح');
    }

    public function updateCategory(StoreMedicineCategoryRequest $request, int $id): JsonResponse
    {
        $category = MedicineCategory::findOrFail($id);
        $category->update($request->validated());

        return $this->success($this->formatCategory($category->fresh()->loadCount('medicines')), 'تم تحديث التصنيف بنجاح');
    }

    public function destroyCategory(int $id): JsonResponse
    {
        $category = MedicineCategory::findOrFail($id);

        if ($category->medicines()->exists()) {
            return $this->error('لا يمكن حذف تصنيف يحتوي على أدوية', 'CONFLICT', 409);
        }

        $category->delete();

        return $this->success(null, 'تم حذف التصنيف بنجاح');
    }

    public function medicines(Request $request): JsonResponse
    {
        $query = Medicine::with('category')->orderBy('sort_order')->orderBy('name_ar');

        if ($request->filled('category_id')) {
            $query->where('medicine_category_id', (int) $request->category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $items = $query->get()->map(fn ($medicine) => $this->formatMedicine($medicine));

        return $this->success($items);
    }

    public function storeMedicine(StoreMedicineRequest $request): JsonResponse
    {
        $medicine = Medicine::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->created($this->formatMedicine($medicine->load('category')), 'تم إضافة الدواء بنجاح');
    }

    public function updateMedicine(StoreMedicineRequest $request, int $id): JsonResponse
    {
        $medicine = Medicine::findOrFail($id);
        $medicine->update($request->validated());

        return $this->success($this->formatMedicine($medicine->fresh('category')), 'تم تحديث الدواء بنجاح');
    }

    public function destroyMedicine(int $id): JsonResponse
    {
        $medicine = Medicine::findOrFail($id);

        if ($medicine->pharmacyItems()->exists()) {
            return $this->error('لا يمكن حذف دواء مُستخدم في صيدليات', 'CONFLICT', 409);
        }

        $medicine->delete();

        return $this->success(null, 'تم حذف الدواء بنجاح');
    }

    protected function formatCategory(MedicineCategory $category): array
    {
        return [
            'id' => $category->id,
            'name_ar' => $category->name_ar,
            'name_en' => $category->name_en,
            'icon' => $category->icon,
            'sort_order' => $category->sort_order,
            'is_active' => $category->is_active,
            'medicines_count' => $category->medicines_count ?? $category->medicines()->count(),
        ];
    }

    protected function formatMedicine(Medicine $medicine): array
    {
        return [
            'id' => $medicine->id,
            'medicine_category_id' => $medicine->medicine_category_id,
            'category_name' => $medicine->category?->name_ar,
            'name_ar' => $medicine->name_ar,
            'name_en' => $medicine->name_en,
            'generic_name' => $medicine->generic_name,
            'barcode' => $medicine->barcode,
            'dosage_form' => $medicine->dosage_form,
            'strength' => $medicine->strength,
            'manufacturer' => $medicine->manufacturer,
            'description_ar' => $medicine->description_ar,
            'sort_order' => $medicine->sort_order,
            'is_active' => $medicine->is_active,
        ];
    }
}
