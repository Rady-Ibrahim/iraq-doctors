<?php

namespace Modules\Doctor\Http\Requests\Web;

use App\Http\Requests\ApiFormRequest;
use Modules\Doctor\Support\DoctorStaffPermissions;

class StoreDoctorStaffRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone|regex:/^[0-9]{10,15}$/',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:' . implode(',', array_keys(DoctorStaffPermissions::ALL)),
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'رقم الهاتف غير صحيح',
            'phone.unique' => 'رقم الهاتف مسجل بالفعل',
            'email.unique' => 'البريد الإلكتروني مسجل بالفعل',
            'password.confirmed' => 'كلمات المرور غير متطابقة',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
        ];
    }
}
