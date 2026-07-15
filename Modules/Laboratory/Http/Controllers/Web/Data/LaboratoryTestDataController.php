<?php

namespace Modules\Laboratory\Http\Controllers\Web\Data;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Laboratory\Http\Requests\Web\StoreLabTestCategoryRequest;
use Modules\Laboratory\Http\Requests\Web\StoreLaboratoryTestItemRequest;
use Modules\Laboratory\Http\Requests\Web\UpdateLaboratoryTestItemRequest;
use Modules\Laboratory\Models\LabTestCategory;
use Modules\Laboratory\Models\Laboratory;
use Modules\Laboratory\Services\Web\LaboratoryTestService;

class LaboratoryTestDataController extends Controller
{
    use ApiResponse;

    public function __construct(private LaboratoryTestService $testService) {}

    protected function resolveLaboratory(): Laboratory
    {
        return Laboratory::where('user_id', auth('web')->id())->firstOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $laboratory = $this->resolveLaboratory();
        $items = $this->testService->listItems(
            $laboratory->id,
            $request->integer('category_id') ?: null,
            $request->input('search')
        );

        return $this->success($items);
    }

    public function catalog(Request $request): JsonResponse
    {
        $laboratory = $this->resolveLaboratory();
        $tests = $this->testService->suggest(
            $laboratory->id,
            $request->input('search'),
            $request->integer('category_id') ?: null
        );

        return $this->success($tests);
    }

    public function suggest(Request $request): JsonResponse
    {
        $laboratory = $this->resolveLaboratory();

        return $this->success(
            $this->testService->suggest(
                $laboratory->id,
                $request->input('q', $request->input('search')),
                $request->integer('category_id') ?: null
            )
        );
    }

    public function categories(Request $request): JsonResponse
    {
        $activeOnly = $request->boolean('active_only');

        return $this->success($this->testService->listCategories($activeOnly));
    }

    public function storeCategory(StoreLabTestCategoryRequest $request): JsonResponse
    {
        $this->resolveLaboratory();
        $category = $this->testService->createCategory($request->validated());

        return $this->created(
            $this->testService->formatCategory($category->loadCount('tests')),
            'تم إضافة التصنيف بنجاح'
        );
    }

    public function updateCategory(StoreLabTestCategoryRequest $request, string $categoryId): JsonResponse
    {
        $this->resolveLaboratory();
        $category = LabTestCategory::findOrFail((int) $categoryId);
        $updated = $this->testService->updateCategory($category, $request->validated());

        return $this->success(
            $this->testService->formatCategory($updated),
            'تم تحديث التصنيف بنجاح'
        );
    }

    public function store(StoreLaboratoryTestItemRequest $request): JsonResponse
    {
        $laboratory = $this->resolveLaboratory();
        $item = $this->testService->createItem($laboratory, $request->validated());

        return $this->created(
            $this->testService->formatItem($item->load(['labTest.category'])),
            'تم إضافة التحليل لمعملك بنجاح'
        );
    }

    public function update(string $itemId, UpdateLaboratoryTestItemRequest $request): JsonResponse
    {
        $laboratory = $this->resolveLaboratory();
        $item = $this->testService->findItemForLaboratory((int) $itemId, $laboratory->id);
        $updated = $this->testService->updateItem($item, $laboratory->id, $request->validated());

        return $this->success(
            $this->testService->formatItem($updated),
            'تم تحديث التحليل بنجاح'
        );
    }

    public function destroy(string $itemId): JsonResponse
    {
        $laboratory = $this->resolveLaboratory();
        $item = $this->testService->findItemForLaboratory((int) $itemId, $laboratory->id);
        $this->testService->deleteItem($item, $laboratory->id);

        return $this->success(null, 'تم حذف التحليل من معملك');
    }
}
