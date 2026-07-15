<?php

namespace App\Support;

use Illuminate\Support\Str;
use Modules\Laboratory\Models\LabTest;
use Modules\Pharmacy\Models\Medicine;

class CatalogDictionary
{
    public static function normalizeName(string $name): string
    {
        return Str::lower(trim(preg_replace('/\s+/u', ' ', $name) ?? $name));
    }

    public static function findOrCreateMedicine(array $data, ?int $pharmacyId = null): Medicine
    {
        if (! empty($data['medicine_id'])) {
            return Medicine::active()->findOrFail($data['medicine_id']);
        }

        $nameAr = trim($data['name_ar'] ?? '');
        if ($nameAr === '') {
            throw new \InvalidArgumentException('اسم الدواء مطلوب');
        }

        if (! empty($data['barcode'])) {
            $existing = Medicine::query()
                ->where('barcode', $data['barcode'])
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $normalized = self::normalizeName($nameAr);
        $generic = ! empty($data['generic_name']) ? self::normalizeName($data['generic_name']) : null;

        $existing = Medicine::query()
            ->whereRaw('LOWER(TRIM(name_ar)) = ?', [$normalized])
            ->when($generic, fn ($q) => $q->whereRaw('LOWER(TRIM(COALESCE(generic_name, ""))) = ?', [$generic]))
            ->first();

        if ($existing) {
            return $existing;
        }

        return Medicine::create([
            'medicine_category_id' => $data['medicine_category_id'],
            'name_ar' => $nameAr,
            'name_en' => $data['name_en'] ?? null,
            'generic_name' => $data['generic_name'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'dosage_form' => $data['dosage_form'] ?? null,
            'strength' => $data['strength'] ?? null,
            'manufacturer' => $data['manufacturer'] ?? null,
            'description_ar' => $data['description_ar'] ?? null,
            'is_active' => true,
            'created_by_pharmacy_id' => $pharmacyId,
        ]);
    }

    public static function findOrCreateLabTest(array $data, ?int $laboratoryId = null): LabTest
    {
        if (! empty($data['lab_test_id'])) {
            return LabTest::active()->findOrFail($data['lab_test_id']);
        }

        $nameAr = trim($data['name_ar'] ?? '');
        if ($nameAr === '') {
            throw new \InvalidArgumentException('اسم التحليل مطلوب');
        }

        if (! empty($data['code'])) {
            $existing = LabTest::query()
                ->where('code', $data['code'])
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $normalized = self::normalizeName($nameAr);

        $existing = LabTest::query()
            ->whereRaw('LOWER(TRIM(name_ar)) = ?', [$normalized])
            ->first();

        if ($existing) {
            return $existing;
        }

        return LabTest::create([
            'lab_test_category_id' => $data['lab_test_category_id'],
            'name_ar' => $nameAr,
            'name_en' => $data['name_en'] ?? null,
            'code' => $data['code'] ?? null,
            'sample_type' => $data['sample_type'] ?? null,
            'description_ar' => $data['description_ar'] ?? null,
            'is_active' => true,
            'created_by_laboratory_id' => $laboratoryId,
        ]);
    }
}
