<?php

namespace Modules\Auth\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;

class LoginRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => 'required|string',
            'password' => 'required|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'رقم الهاتف مطلوب',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
        ];
    }
}
