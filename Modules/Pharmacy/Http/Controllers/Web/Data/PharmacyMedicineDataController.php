<?php

namespace Modules\Pharmacy\Http\Controllers\Web\Data;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Pharmacy\Http\Requests\Web\StoreMedicineCategoryRequest;
use Modules\Pharmacy\Http\Requests\Web\StorePharmacyMedicineRequest;
use Modules\Pharmacy\Http\Requests\Web\UpdatePharmacyMedicineRequest;
use Modules\Pharmacy\Models\MedicineCategory;
use Modules\Pharmacy\Models\Pharmacy;
use Modules\Pharmacy\Services\Web\PharmacyMedicineService;

class PharmacyMedicineDataController extends Controller
{
    use ApiResponse;

    public function __construct(private PharmacyMedicineService $medicineService) {}

    protected function resolvePharmacy(): Pharmacy
    {
        return Pharmacy::where('user_id', auth('web')->id())->firstOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $pharmacy = $this->resolvePharmacy();
        $items = $this->medicineService->listItems(
            $pharmacy->id,
            $request->integer('category_id') ?: null,
            $request->input('search')
        );

        return $this->success($items);
    }

    public function catalog(Request $request): JsonResponse
    {
        $pharmacy = $this->resolvePharmacy();
        $medicines = $this->medicineService->suggest(
            $pharmacy->id,
            $request->input('search'),
            $request->integer('category_id') ?: null
        );

        return $this->success($medicines);
    }

    public function suggest(Request $request): JsonResponse
    {
        $pharmacy = $this->resolvePharmacy();

        return $this->success(
            $this->medicineService->suggest(
                $pharmacy->id,
                $request->input('q', $request->input('search')),
                $request->integer('category_id') ?: null
            )
        );
    }

    public function categories(Request $request): JsonResponse
    {
        $activeOnly = $request->boolean('active_only');

        return $this->success($this->medicineService->listCategories($activeOnly));
    }

    public function storeCategory(StoreMedicineCategoryRequest $request): JsonResponse
    {
        $this->resolvePharmacy();
        $category = $this->medicineService->createCategory($request->validated());

        return $this->created(
            $this->medicineService->formatCategory($category->loadCount('medicines')),
            'تم إضافة التصنيف بنجاح'
        );
    }

    public function updateCategory(StoreMedicineCategoryRequest $request, string $categoryId): JsonResponse
    {
        $this->resolvePharmacy();
        $category = MedicineCategory::findOrFail((int) $categoryId);
        $updated = $this->medicineService->updateCategory($category, $request->validated());

        return $this->success(
            $this->medicineService->formatCategory($updated),
            'تم تحديث التصنيف بنجاح'
        );
    }

    public function store(StorePharmacyMedicineRequest $request): JsonResponse
    {
        $pharmacy = $this->resolvePharmacy();
        $item = $this->medicineService->createItem($pharmacy, $request->validated());

        return $this->created(
            $this->medicineService->formatItem($item->load(['medicine.category'])),
            'تم إضافة الدواء لصيدليتك بنجاح'
        );
    }

    public function update(string $itemId, UpdatePharmacyMedicineRequest $request): JsonResponse
    {
        $pharmacy = $this->resolvePharmacy();
        $item = $this->medicineService->findItemForPharmacy((int) $itemId, $pharmacy->id);
        $updated = $this->medicineService->updateItem($item, $pharmacy->id, $request->validated());

        return $this->success(
            $this->medicineService->formatItem($updated),
            'تم تحديث الدواء بنجاح'
        );
    }

    public function destroy(string $itemId): JsonResponse
    {
        $pharmacy = $this->resolvePharmacy();
        $item = $this->medicineService->findItemForPharmacy((int) $itemId, $pharmacy->id);
        $this->medicineService->deleteItem($item, $pharmacy->id);

        return $this->success(null, 'تم حذف الدواء من صيدليتك');
    }
}
