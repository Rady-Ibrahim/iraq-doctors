<?php

namespace Modules\Auth\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateGhostPatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'age' => 'nullable|integer|min:0|max:150',
            'gender' => 'nullable|in:male,female',
            'visit_date' => 'nullable|date',
            'initial_notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'age.integer' => 'العمر يجب أن يكون رقماً',
            'gender.in' => 'الجنس يجب أن يكون ذكر أو أنثى',
            'visit_date.date' => 'تاريخ الزيارة غير صحيح',
        ];
    }
}
