<?php

namespace Modules\Laboratory\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class VerifyLaboratoryPhoneRequest extends FormRequest
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

    public function attributes(): array
    {
        return [
            'firebase_token' => 'رمز التحقق',
        ];
    }
}
