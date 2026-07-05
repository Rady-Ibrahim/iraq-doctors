<?php

namespace Modules\Laboratory\Services\Web;

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

    public function catalog(?int $categoryId = null, ?string $search = null)
    {
        $query = LabTest::with('category')->active()->orderBy('sort_order')->orderBy('name_ar');

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

        return $query->get()->map(fn ($test) => [
            'id' => $test->id,
            'lab_test_category_id' => $test->lab_test_category_id,
            'category_name' => $test->category?->name_ar,
            'name_ar' => $test->name_ar,
            'name_en' => $test->name_en,
            'code' => $test->code,
            'sample_type' => $test->sample_type,
            'description_ar' => $test->description_ar,
        ]);
    }

    public function activeCategories()
    {
        return LabTestCategory::active()
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en', 'icon']);
    }

    public function createItem(Laboratory $laboratory, array $data): LaboratoryTestItem
    {
        $test = LabTest::active()->findOrFail($data['lab_test_id']);

        return LaboratoryTestItem::create([
            'laboratory_id' => $laboratory->id,
            'lab_test_id' => $test->id,
            'price' => $data['price'],
            'result_hours' => $data['result_hours'],
            'is_available' => $data['is_available'] ?? true,
            'notes' => $data['notes'] ?? null,
        ]);
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
