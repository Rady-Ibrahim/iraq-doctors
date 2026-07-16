<?php

namespace Modules\Doctor\Http\Requests\Web;

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
            'email' => 'nullable|email',
            'gender' => 'nullable|in:male,female',
            'age' => 'nullable|integer|min:0|max:120',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'gender.in' => 'الجنس يجب أن يكون ذكر أو أنثى',
            'age.integer' => 'العمر يجب أن يكون رقماً',
            'age.min' => 'العمر غير صالح',
            'age.max' => 'العمر غير صالح',
        ];
    }
}
