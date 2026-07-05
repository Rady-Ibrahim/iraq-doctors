<?php

namespace Modules\Admin\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'medicine_category_id' => 'required|integer|exists:medicine_categories,id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:50',
            'dosage_form' => 'nullable|string|max:100',
            'strength' => 'nullable|string|max:100',
            'manufacturer' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string|max:2000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'medicine_category_id' => 'التصنيف',
            'name_ar' => 'اسم الدواء',
            'name_en' => 'الاسم بالإنجليزية',
            'generic_name' => 'الاسم العلمي',
            'barcode' => 'الباركود',
            'dosage_form' => 'شكل الدواء',
            'strength' => 'التركيز',
            'manufacturer' => 'الشركة المصنعة',
            'description_ar' => 'الوصف',
            'sort_order' => 'الترتيب',
        ];
    }

    public function messages(): array
    {
        return [
            'medicine_category_id.required' => 'يجب اختيار تصنيف الدواء',
            'medicine_category_id.exists' => 'التصنيف غير صالح',
            'name_ar.required' => 'اسم الدواء مطلوب',
        ];
    }
}
