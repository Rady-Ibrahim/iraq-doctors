<?php

namespace Modules\Laboratory\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLaboratoryTestItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price' => 'sometimes|required|numeric|min:0',
            'result_hours' => 'sometimes|required|integer|min:1|max:720',
            'is_available' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function attributes(): array
    {
        return [
            'price' => 'السعر',
            'result_hours' => 'مدة النتيجة',
            'notes' => 'ملاحظات',
        ];
    }

    public function messages(): array
    {
        return [
            'price.required' => 'السعر مطلوب',
            'price.numeric' => 'السعر يجب أن يكون رقماً',
            'price.min' => 'السعر لا يمكن أن يكون سالباً',
            'result_hours.required' => 'مدة النتيجة مطلوبة',
            'result_hours.min' => 'مدة النتيجة يجب أن تكون ساعة واحدة على الأقل',
        ];
    }
}
