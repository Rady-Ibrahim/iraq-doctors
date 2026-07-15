<?php

namespace Modules\Pharmacy\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Pharmacy\Models\Pharmacy;

class StorePharmacyMedicineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pharmacyId = optional(
            Pharmacy::where('user_id', auth('web')->id())->first()
        )->id;

        return [
            'medicine_id' => 'nullable|integer|exists:medicines,id',
            'medicine_category_id' => 'required_without:medicine_id|integer|exists:medicine_categories,id',
            'name_ar' => 'required_without:medicine_id|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:64',
            'dosage_form' => 'nullable|string|max:100',
            'strength' => 'nullable|string|max:100',
            'manufacturer' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0|max:999999',
            'expiry_date' => 'nullable|date|after_or_equal:today',
            'is_available' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('medicine_id') && ! $this->filled('name_ar')) {
                $validator->errors()->add('name_ar', 'اسم الدواء مطلوب');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'medicine_id' => 'الدواء',
            'medicine_category_id' => 'التصنيف',
            'name_ar' => 'اسم الدواء',
            'price' => 'السعر',
            'stock_quantity' => 'الكمية في المخزون',
            'expiry_date' => 'تاريخ انتهاء الصلاحية',
            'notes' => 'ملاحظات',
        ];
    }

    public function messages(): array
    {
        return [
            'medicine_category_id.required_without' => 'التصنيف مطلوب عند إضافة دواء جديد',
            'name_ar.required_without' => 'اسم الدواء مطلوب',
            'price.required' => 'السعر مطلوب',
            'stock_quantity.required' => 'كمية المخزون مطلوبة',
        ];
    }
}
