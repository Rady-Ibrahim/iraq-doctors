<?php

namespace Modules\Admin\Http\Controllers\Api;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Admin\Http\Requests\Web\StoreLabTestCategoryRequest;
use Modules\Admin\Http\Requests\Web\StoreLabTestRequest;
use Modules\Laboratory\Models\LabTest;
use Modules\Laboratory\Models\LabTestCategory;

class AdminLabTestApiController extends Controller
{
    use ApiResponse;

    public function categories(): JsonResponse
    {
        $items = LabTestCategory::withCount('tests')
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get()
            ->map(fn ($cat) => $this->formatCategory($cat));

        return $this->success($items);
    }

    public function storeCategory(StoreLabTestCategoryRequest $request): JsonResponse
    {
        $category = LabTestCategory::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->created($this->formatCategory($category->loadCount('tests')), 'تم إضافة التصنيف بنجاح');
    }

    public function updateCategory(StoreLabTestCategoryRequest $request, int $id): JsonResponse
    {
        $category = LabTestCategory::findOrFail($id);
        $category->update($request->validated());

        return $this->success($this->formatCategory($category->fresh()->loadCount('tests')), 'تم تحديث التصنيف بنجاح');
    }

    public function destroyCategory(int $id): JsonResponse
    {
        $category = LabTestCategory::findOrFail($id);

        if ($category->tests()->exists()) {
            return $this->error('لا يمكن حذف تصنيف يحتوي على تحاليل', 'CONFLICT', 409);
        }

        $category->delete();

        return $this->success(null, 'تم حذف التصنيف بنجاح');
    }

    public function tests(Request $request): JsonResponse
    {
        $query = LabTest::with('category')->orderBy('sort_order')->orderBy('name_ar');

        if ($request->filled('category_id')) {
            $query->where('lab_test_category_id', (int) $request->category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $items = $query->get()->map(fn ($test) => $this->formatTest($test));

        return $this->success($items);
    }

    public function storeTest(StoreLabTestRequest $request): JsonResponse
    {
        $test = LabTest::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->created($this->formatTest($test->load('category')), 'تم إضافة التحليل بنجاح');
    }

    public function updateTest(StoreLabTestRequest $request, int $id): JsonResponse
    {
        $test = LabTest::findOrFail($id);
        $test->update($request->validated());

        return $this->success($this->formatTest($test->fresh('category')), 'تم تحديث التحليل بنجاح');
    }

    public function destroyTest(int $id): JsonResponse
    {
        $test = LabTest::findOrFail($id);

        if ($test->laboratoryItems()->exists()) {
            return $this->error('لا يمكن حذف تحليل مُستخدم في مختبرات', 'CONFLICT', 409);
        }

        $test->delete();

        return $this->success(null, 'تم حذف التحليل بنجاح');
    }

    protected function formatCategory(LabTestCategory $category): array
    {
        return [
            'id' => $category->id,
            'name_ar' => $category->name_ar,
            'name_en' => $category->name_en,
            'icon' => $category->icon,
            'sort_order' => $category->sort_order,
            'is_active' => $category->is_active,
            'tests_count' => $category->tests_count ?? $category->tests()->count(),
        ];
    }

    protected function formatTest(LabTest $test): array
    {
        return [
            'id' => $test->id,
            'lab_test_category_id' => $test->lab_test_category_id,
            'category_name' => $test->category?->name_ar,
            'name_ar' => $test->name_ar,
            'name_en' => $test->name_en,
            'code' => $test->code,
            'description_ar' => $test->description_ar,
            'sample_type' => $test->sample_type,
            'sort_order' => $test->sort_order,
            'is_active' => $test->is_active,
        ];
    }
}
