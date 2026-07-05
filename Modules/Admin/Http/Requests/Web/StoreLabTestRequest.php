<?php

namespace Modules\Admin\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lab_test_category_id' => 'required|integer|exists:lab_test_categories,id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:50',
            'description_ar' => 'nullable|string|max:2000',
            'sample_type' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'lab_test_category_id' => 'التصنيف',
            'name_ar' => 'اسم التحليل',
            'name_en' => 'الاسم بالإنجليزية',
            'code' => 'الرمز',
            'description_ar' => 'الوصف',
            'sample_type' => 'نوع العينة',
            'sort_order' => 'الترتيب',
        ];
    }

    public function messages(): array
    {
        return [
            'lab_test_category_id.required' => 'يجب اختيار تصنيف التحليل',
            'lab_test_category_id.exists' => 'التصنيف غير صالح',
            'name_ar.required' => 'اسم التحليل مطلوب',
        ];
    }
}
