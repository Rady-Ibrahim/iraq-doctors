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
            'phone'    => 'nullable|string|regex:/^[0-9]{10,15}$/',
            'email'    => 'nullable|email',
            'password' => 'required|string|min:8',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if (empty($this->phone) && empty($this->email)) {
                $v->errors()->add('identifier', 'يجب إدخال رقم الهاتف أو البريد الإلكتروني');
            }
        });
    }

    public function messages(): array
    {
        return [
            'phone.regex'    => 'رقم الهاتف غير صحيح',
            'email.email'    => 'البريد الإلكتروني غير صحيح',
            'password.min'   => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
        ];
    }
}
