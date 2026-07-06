<?php

namespace Modules\Doctor\Http\Requests\Web;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;
use Modules\Doctor\Models\DoctorStaffMember;
use Modules\Doctor\Support\DoctorStaffPermissions;

class UpdateDoctorStaffRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $memberId = (int) $this->route('memberId');
        $member = DoctorStaffMember::find($memberId);
        $userId = $member?->user_id;

        return [
            'name' => 'sometimes|string|max:255',
            'phone' => [
                'sometimes',
                'string',
                'regex:/^[0-9]{10,15}$/',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string|in:' . implode(',', array_keys(DoctorStaffPermissions::ALL)),
            'status' => 'sometimes|in:active,inactive',
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
