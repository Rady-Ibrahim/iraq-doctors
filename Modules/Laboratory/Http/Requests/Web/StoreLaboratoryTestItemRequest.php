<?php

namespace Modules\Laboratory\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLaboratoryTestItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $laboratoryId = optional(
            \Modules\Laboratory\Models\Laboratory::where('user_id', auth('web')->id())->first()
        )->id;

        return [
            'lab_test_id' => [
                'required',
                'integer',
                'exists:lab_tests,id',
                Rule::unique('laboratory_test_items', 'lab_test_id')->where('laboratory_id', $laboratoryId),
            ],
            'price' => 'required|numeric|min:0',
            'result_hours' => 'required|integer|min:1|max:720',
            'is_available' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function attributes(): array
    {
        return [
            'lab_test_id' => 'التحليل',
            'price' => 'السعر',
            'result_hours' => 'مدة النتيجة',
            'notes' => 'ملاحظات',
        ];
    }

    public function messages(): array
    {
        return [
            'lab_test_id.required' => 'يجب اختيار تحليل',
            'lab_test_id.exists' => 'التحليل غير صالح',
            'lab_test_id.unique' => 'هذا التحليل مُضاف بالفعل لمعملك',
            'price.required' => 'السعر مطلوب',
            'price.numeric' => 'السعر يجب أن يكون رقماً',
            'price.min' => 'السعر لا يمكن أن يكون سالباً',
            'result_hours.required' => 'مدة النتيجة مطلوبة',
            'result_hours.min' => 'مدة النتيجة يجب أن تكون ساعة واحدة على الأقل',
            'result_hours.max' => 'مدة النتيجة طويلة جداً',
        ];
    }
}
