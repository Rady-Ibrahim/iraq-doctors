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
            'phone' => 'required|string|regex:/^[0-9]{10,15}$/',
            'code' => 'required|string|size:6|regex:/^[0-9]{6}$/',
            'type' => 'required|in:register,login,reset_password',
        ];
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
