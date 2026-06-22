<?php

namespace Modules\Doctor\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class VerifyDoctorEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'كود التفعيل مطلوب',
            'code.size' => 'كود التفعيل يجب أن يكون 6 أرقام',
        ];
    }
}
