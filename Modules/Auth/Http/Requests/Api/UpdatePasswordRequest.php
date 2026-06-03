<?php

namespace Modules\Auth\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!Hash::check($value, $this->user()->password)) {
                        $fail('كلمة المرور الحالية غير صحيحة');
                    }
                },
            ],
            'new_password' => 'required|string|min:8|different:current_password',
            'new_password_confirmation' => 'required|string|same:new_password',
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'كلمة المرور الحالية مطلوبة',
            'new_password.required' => 'كلمة المرور الجديدة مطلوبة',
            'new_password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'new_password.different' => 'كلمة المرور الجديدة يجب أن تكون مختلفة عن الحالية',
            'new_password_confirmation.required' => 'تأكيد كلمة المرور مطلوب',
            'new_password_confirmation.same' => 'تأكيد كلمة المرور لا يطابق كلمة المرور الجديدة',
        ];
    }
}
