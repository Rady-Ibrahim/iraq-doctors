<?php

namespace Modules\Pharmacy\Services\Web;

use App\Support\CatalogDictionary;
use Illuminate\Support\Facades\DB;
use Modules\Pharmacy\Models\Medicine;
use Modules\Pharmacy\Models\MedicineCategory;
use Modules\Pharmacy\Models\Pharmacy;
use Modules\Pharmacy\Models\PharmacyMedicine;

class PharmacyMedicineService
{
    public function listItems(int $pharmacyId, ?int $categoryId = null, ?string $search = null)
    {
        $query = PharmacyMedicine::with(['medicine.category'])
            ->where('pharmacy_id', $pharmacyId)
            ->orderByDesc('created_at');

        if ($categoryId) {
            $query->whereHas('medicine', fn ($q) => $q->where('medicine_category_id', $categoryId));
        }

        if ($search) {
            $query->whereHas('medicine', function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        return $query->get()->map(fn ($item) => $this->formatItem($item));
    }

    public function suggest(int $pharmacyId, ?string $search = null, ?int $categoryId = null, int $limit = 20)
    {
        $query = Medicine::with('category')
            ->active()
            ->orderBy('name_ar');

        if ($categoryId) {
            $query->where('medicine_category_id', $categoryId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $alreadyAdded = PharmacyMedicine::where('pharmacy_id', $pharmacyId)
            ->pluck('medicine_id')
            ->all();

        return $query->limit($limit)->get()->map(fn (Medicine $medicine) => [
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
            'already_in_pharmacy' => in_array($medicine->id, $alreadyAdded, true),
        ]);
    }

    /** @deprecated Use suggest() — kept for backward compatibility */
    public function catalog(?int $categoryId = null, ?string $search = null)
    {
        return $this->suggest(0, $search, $categoryId, 100);
    }

    public function activeCategories()
    {
        return $this->listCategories(activeOnly: true);
    }

    public function listCategories(bool $activeOnly = false)
    {
        $query = MedicineCategory::withCount('medicines')
            ->orderBy('sort_order')
            ->orderBy('name_ar');

        if ($activeOnly) {
            $query->active();
        }

        return $query->get()->map(fn (MedicineCategory $category) => $this->formatCategory($category));
    }

    public function createCategory(array $data): MedicineCategory
    {
        $maxOrder = (int) MedicineCategory::max('sort_order');

        return MedicineCategory::create([
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'icon' => $data['icon'] ?? null,
            'sort_order' => $data['sort_order'] ?? ($maxOrder + 1),
            'is_active' => true,
        ]);
    }

    public function updateCategory(MedicineCategory $category, array $data): MedicineCategory
    {
        $category->update([
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'icon' => $data['icon'] ?? null,
            'sort_order' => $data['sort_order'] ?? $category->sort_order,
        ]);

        return $category->fresh()->loadCount('medicines');
    }

    public function formatCategory(MedicineCategory $category): array
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

    public function createItem(Pharmacy $pharmacy, array $data): PharmacyMedicine
    {
        return DB::transaction(function () use ($pharmacy, $data) {
            $medicine = CatalogDictionary::findOrCreateMedicine($data, $pharmacy->id);

            $existing = PharmacyMedicine::where('pharmacy_id', $pharmacy->id)
                ->where('medicine_id', $medicine->id)
                ->first();

            if ($existing) {
                throw new \InvalidArgumentException('هذا الدواء مُضاف بالفعل لصيدليتك');
            }

            return PharmacyMedicine::create([
                'pharmacy_id' => $pharmacy->id,
                'medicine_id' => $medicine->id,
                'price' => $data['price'],
                'stock_quantity' => $data['stock_quantity'] ?? 0,
                'expiry_date' => $data['expiry_date'] ?? null,
                'is_available' => $data['is_available'] ?? true,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public function updateItem(PharmacyMedicine $item, int $pharmacyId, array $data): PharmacyMedicine
    {
        if ($item->pharmacy_id !== $pharmacyId) {
            abort(403);
        }

        $item->update($data);

        return $item->fresh(['medicine.category']);
    }

    public function deleteItem(PharmacyMedicine $item, int $pharmacyId): void
    {
        if ($item->pharmacy_id !== $pharmacyId) {
            abort(403);
        }

        $item->delete();
    }

    public function findItemForPharmacy(int $itemId, int $pharmacyId): PharmacyMedicine
    {
        return PharmacyMedicine::with(['medicine.category'])
            ->where('pharmacy_id', $pharmacyId)
            ->findOrFail($itemId);
    }

    public function formatItem(PharmacyMedicine $item): array
    {
        $medicine = $item->medicine;

        return [
            'id' => $item->id,
            'medicine_id' => $item->medicine_id,
            'name_ar' => $medicine?->name_ar,
            'name_en' => $medicine?->name_en,
            'generic_name' => $medicine?->generic_name,
            'barcode' => $medicine?->barcode,
            'category_name' => $medicine?->category?->name_ar,
            'dosage_form' => $medicine?->dosage_form,
            'strength' => $medicine?->strength,
            'manufacturer' => $medicine?->manufacturer,
            'price' => $item->price,
            'stock_quantity' => $item->stock_quantity,
            'expiry_date' => $item->expiry_date?->format('Y-m-d'),
            'is_available' => $item->is_available,
            'notes' => $item->notes,
        ];
    }
}
