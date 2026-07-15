<?php

namespace Modules\Laboratory\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreLaboratoryTestItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lab_test_id' => 'nullable|integer|exists:lab_tests,id',
            'lab_test_category_id' => 'required_without:lab_test_id|integer|exists:lab_test_categories,id',
            'name_ar' => 'required_without:lab_test_id|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:64',
            'sample_type' => 'nullable|string|max:100',
            'description_ar' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0',
            'result_hours' => 'required|integer|min:1|max:720',
            'is_available' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('lab_test_id') && ! $this->filled('name_ar')) {
                $validator->errors()->add('name_ar', 'اسم التحليل مطلوب');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'lab_test_id' => 'التحليل',
            'lab_test_category_id' => 'التصنيف',
            'name_ar' => 'اسم التحليل',
            'price' => 'السعر',
            'result_hours' => 'مدة النتيجة',
            'notes' => 'ملاحظات',
        ];
    }

    public function messages(): array
    {
        return [
            'lab_test_category_id.required_without' => 'التصنيف مطلوب عند إضافة تحليل جديد',
            'name_ar.required_without' => 'اسم التحليل مطلوب',
            'price.required' => 'السعر مطلوب',
            'result_hours.required' => 'مدة النتيجة مطلوبة',
        ];
    }
}
