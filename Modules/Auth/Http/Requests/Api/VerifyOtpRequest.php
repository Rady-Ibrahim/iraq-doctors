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
            'phone' => 'required|string',
            'code' => ['required', 'digits:6'],
            'type' => 'required|in:register,login,password_reset,reset_password,phone_verify',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'رقم الهاتف مطلوب',
            'code.digits' => 'الكود يجب أن يكون 6 أرقام',
            'type.in' => 'نوع OTP غير صحيح',
        ];
    }
}
