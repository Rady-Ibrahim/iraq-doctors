<?php

namespace Modules\Doctor\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class VerifyDoctorPhoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'كود التحقق مطلوب',
            'code.digits' => 'كود التحقق يجب أن يكون 6 أرقام',
        ];
    }
}
