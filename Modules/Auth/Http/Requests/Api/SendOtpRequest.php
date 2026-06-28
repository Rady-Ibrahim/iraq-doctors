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
            'phone' => 'required|string',
            'type' => 'required|in:register,login,password_reset,phone_verify',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'رقم الهاتف مطلوب',
            'type.in' => 'نوع OTP غير صحيح',
        ];
    }
}
