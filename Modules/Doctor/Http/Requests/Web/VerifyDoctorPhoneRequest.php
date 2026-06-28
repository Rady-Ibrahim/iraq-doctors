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
            'firebase_token' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'firebase_token.required' => 'رمز التحقق من Firebase مطلوب. أرسل كود SMS أولاً ثم أكّد التفعيل.',
        ];
    }
}
