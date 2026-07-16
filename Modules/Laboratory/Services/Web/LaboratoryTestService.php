<?php

namespace Modules\Laboratory\Services\Web;

use App\Support\CatalogDictionary;
use Illuminate\Support\Facades\DB;
use Modules\Laboratory\Models\LabTest;
use Modules\Laboratory\Models\LabTestCategory;
use Modules\Laboratory\Models\Laboratory;
use Modules\Laboratory\Models\LaboratoryTestItem;

class LaboratoryTestService
{
    public function listItems(int $laboratoryId, ?int $categoryId = null, ?string $search = null)
    {
        $query = LaboratoryTestItem::with(['labTest.category'])
            ->where('laboratory_id', $laboratoryId)
            ->orderByDesc('created_at');

        if ($categoryId) {
            $query->whereHas('labTest', fn ($q) => $q->where('lab_test_category_id', $categoryId));
        }

        if ($search) {
            $query->whereHas('labTest', function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return $query->get()->map(fn ($item) => $this->formatItem($item));
    }

    public function suggest(int $laboratoryId, ?string $search = null, ?int $categoryId = null, int $limit = 20)
    {
        $query = LabTest::with('category')
            ->active()
            ->orderBy('name_ar');

        if ($categoryId) {
            $query->where('lab_test_category_id', $categoryId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $alreadyAdded = LaboratoryTestItem::where('laboratory_id', $laboratoryId)
            ->pluck('lab_test_id')
            ->all();

        return $query->limit($limit)->get()->map(fn (LabTest $test) => [
            'id' => $test->id,
            'lab_test_category_id' => $test->lab_test_category_id,
            'category_name' => $test->category?->name_ar,
            'name_ar' => $test->name_ar,
            'name_en' => $test->name_en,
            'code' => $test->code,
            'sample_type' => $test->sample_type,
            'description_ar' => $test->description_ar,
            'already_in_laboratory' => in_array($test->id, $alreadyAdded, true),
        ]);
    }

    /** @deprecated Use suggest() */
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
        $query = LabTestCategory::withCount('tests')
            ->orderBy('sort_order')
            ->orderBy('name_ar');

        if ($activeOnly) {
            $query->active();
        }

        return $query->get()->map(fn (LabTestCategory $category) => $this->formatCategory($category));
    }

    public function createCategory(array $data): LabTestCategory
    {
        $maxOrder = (int) LabTestCategory::max('sort_order');

        return LabTestCategory::create([
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'icon' => $data['icon'] ?? null,
            'sort_order' => $data['sort_order'] ?? ($maxOrder + 1),
            'is_active' => true,
        ]);
    }

    public function updateCategory(LabTestCategory $category, array $data): LabTestCategory
    {
        $category->update([
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'icon' => $data['icon'] ?? null,
            'sort_order' => $data['sort_order'] ?? $category->sort_order,
        ]);

        return $category->fresh()->loadCount('tests');
    }

    public function formatCategory(LabTestCategory $category): array
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

    public function createItem(Laboratory $laboratory, array $data): LaboratoryTestItem
    {
        return DB::transaction(function () use ($laboratory, $data) {
            $test = CatalogDictionary::findOrCreateLabTest($data, $laboratory->id);

            $existing = LaboratoryTestItem::where('laboratory_id', $laboratory->id)
                ->where('lab_test_id', $test->id)
                ->first();

            if ($existing) {
                throw new \InvalidArgumentException('هذا التحليل مُضاف بالفعل لمختبرك');
            }

            return LaboratoryTestItem::create([
                'laboratory_id' => $laboratory->id,
                'lab_test_id' => $test->id,
                'price' => $data['price'],
                'result_hours' => $data['result_hours'],
                'is_available' => $data['is_available'] ?? true,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public function updateItem(LaboratoryTestItem $item, int $laboratoryId, array $data): LaboratoryTestItem
    {
        if ($item->laboratory_id !== $laboratoryId) {
            abort(403);
        }

        $item->update($data);

        return $item->fresh(['labTest.category']);
    }

    public function deleteItem(LaboratoryTestItem $item, int $laboratoryId): void
    {
        if ($item->laboratory_id !== $laboratoryId) {
            abort(403);
        }

        $item->delete();
    }

    public function findItemForLaboratory(int $itemId, int $laboratoryId): LaboratoryTestItem
    {
        return LaboratoryTestItem::with(['labTest.category'])
            ->where('laboratory_id', $laboratoryId)
            ->findOrFail($itemId);
    }

    public function formatItem(LaboratoryTestItem $item): array
    {
        $test = $item->labTest;

        return [
            'id' => $item->id,
            'lab_test_id' => $item->lab_test_id,
            'name_ar' => $test?->name_ar,
            'name_en' => $test?->name_en,
            'code' => $test?->code,
            'category_name' => $test?->category?->name_ar,
            'sample_type' => $test?->sample_type,
            'price' => $item->price,
            'result_hours' => $item->result_hours,
            'is_available' => $item->is_available,
            'notes' => $item->notes,
        ];
    }
}
