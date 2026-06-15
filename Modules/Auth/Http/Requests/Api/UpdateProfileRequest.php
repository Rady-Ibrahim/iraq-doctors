<?php

namespace Modules\Auth\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name'             => 'nullable|string|max:255',
            'email'            => ['nullable', 'email', Rule::unique('users')->ignore($userId)],
            'phone'            => ['nullable', 'string', Rule::unique('users')->ignore($userId)],
            'birthdate'        => 'nullable|date|before:today',
            'gender'           => 'nullable|in:male,female',
            'city'             => 'nullable|string|max:100',
            'district'         => 'nullable|string|max:100',
            'address'          => 'nullable|string|max:500',
            // Doctor-only fields
            'bio'              => 'nullable|string|max:1000',
            'experience_years' => 'nullable|integer|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'          => 'البريد الإلكتروني مستخدم بالفعل',
            'phone.unique'          => 'رقم الهاتف مستخدم بالفعل',
            'birthdate.before'      => 'تاريخ الميلاد غير صحيح',
            'gender.in'             => 'الجنس يجب أن يكون ذكر أو أنثى',
            'bio.max'               => 'السيرة الذاتية يجب أن تكون أقل من 1000 حرف',
            'experience_years.max'  => 'سنوات الخبرة يجب أن تكون أقل من 100 سنة',
        ];
    }
}
