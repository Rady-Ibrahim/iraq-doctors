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
            'governorate_id' => 'required|integer|exists:governorates,id',
            'area' => 'required|string|max:255',
            'address' => 'required|string|max:1000',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'bio_ar' => 'nullable|string|max:2000',
            'experience_years' => 'nullable|integer|min:0|max:60',
            'avatar' => 'required|image|mimes:jpg,jpeg,png|max:2048',
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
            'governorate_id.required' => 'المحافظة مطلوبة',
            'area.required' => 'المنطقة مطلوبة',
            'address.required' => 'عنوان العيادة مطلوب',
            'latitude.required' => 'موقع العيادة (خط العرض) مطلوب',
            'longitude.required' => 'موقع العيادة (خط الطول) مطلوب',
            'avatar.required' => 'الصورة الشخصية مطلوبة',
            'avatar.image' => 'الصورة الشخصية يجب أن تكون صورة',
            'license_document.required' => 'صورة الترخيص مطلوبة',
            'license_document.mimes' => 'صورة الترخيص يجب أن تكون PDF أو صورة',
        ];
    }
}
