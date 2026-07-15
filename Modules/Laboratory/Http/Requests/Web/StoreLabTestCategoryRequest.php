<?php

namespace Modules\Laboratory\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLabTestCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('categoryId');

        return [
            'name_ar' => [
                'required',
                'string',
                'max:255',
                Rule::unique('lab_test_categories', 'name_ar')->ignore($categoryId),
            ],
            'name_en' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ];
    }

    public function attributes(): array
    {
        return [
            'name_ar' => 'الاسم بالعربية',
            'name_en' => 'الاسم بالإنجليزية',
            'icon' => 'الأيقونة',
            'sort_order' => 'الترتيب',
        ];
    }

    public function messages(): array
    {
        return [
            'name_ar.required' => 'اسم التصنيف بالعربية مطلوب',
            'name_ar.unique' => 'هذا التصنيف موجود بالفعل في النظام',
        ];
    }
}
