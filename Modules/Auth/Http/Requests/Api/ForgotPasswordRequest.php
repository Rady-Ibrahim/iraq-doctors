<?php

namespace Modules\Auth\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $user = \Modules\Auth\Models\User::where('phone', $value)->first();
                    if (!$user) {
                        $fail('رقم الهاتف غير موجود');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'رقم الهاتف مطلوب',
        ];
    }
}
