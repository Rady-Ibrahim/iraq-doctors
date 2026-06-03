<?php

namespace Modules\Auth\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
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
            'code' => 'required|string',
            'new_password' => 'required|string|min:8',
            'new_password_confirmation' => 'required|string|same:new_password',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'رقم الهاتف مطلوب',
            'code.required' => 'كود التحقق مطلوب',
            'new_password.required' => 'كلمة المرور الجديدة مطلوبة',
            'new_password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'new_password_confirmation.required' => 'تأكيد كلمة المرور مطلوب',
            'new_password_confirmation.same' => 'تأكيد كلمة المرور لا يطابق كلمة المرور الجديدة',
        ];
    }
}
