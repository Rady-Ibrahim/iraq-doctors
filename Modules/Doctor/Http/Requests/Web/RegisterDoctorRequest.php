<?php

namespace Modules\Doctor\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDoctorRequest extends FormRequest
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
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'speciality_id' => 'required|integer|exists:specialities,id',
            'bio_ar' => 'nullable|string|max:2000',
            'experience_years' => 'nullable|integer|min:0|max:60',
            'license_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'clinic_image' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
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
            'speciality_id.required' => 'التخصص مطلوب',
            'license_document.required' => 'صورة الترخيص مطلوبة',
            'license_document.mimes' => 'صورة الترخيص يجب أن تكون PDF أو صورة',
        ];
    }
}
