<?php

namespace Modules\Auth\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;

class VerifyOtpRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'code'  => ['required', 'digits:6'],
            'type' => 'required|in:register,login,password_reset,reset_password',
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
            'phone.regex' => 'رقم الهاتف غير صحيح',
            'code.size' => 'الكود يجب أن يكون 6 أرقام',
            'code.regex' => 'الكود يجب أن يحتوي على أرقام فقط',
        ];
    }
}
