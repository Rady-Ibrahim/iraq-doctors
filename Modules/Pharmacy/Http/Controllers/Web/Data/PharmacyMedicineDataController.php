<?php

namespace Modules\Pharmacy\Http\Controllers\Web\Data;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Pharmacy\Http\Requests\Web\StorePharmacyMedicineRequest;
use Modules\Pharmacy\Http\Requests\Web\UpdatePharmacyMedicineRequest;
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
        $medicines = $this->medicineService->catalog(
            $request->integer('category_id') ?: null,
            $request->input('search')
        );

        return $this->success($medicines);
    }

    public function categories(): JsonResponse
    {
        return $this->success($this->medicineService->activeCategories());
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
