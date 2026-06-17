<?php

namespace Modules\Auth\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;

class SendOtpRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => 'nullable|string|regex:/^[0-9]{10,15}$/',
            'email' => 'nullable|email',
            'type'  => 'required|in:register,login,password_reset',
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
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'type.in'     => 'نوع OTP غير صحيح',
        ];
    }
}
