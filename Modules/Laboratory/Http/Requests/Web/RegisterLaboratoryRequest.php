<?php

namespace Modules\Laboratory\Http\Requests\Web;

use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class RegisterLaboratoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->email === '') {
            $this->merge(['email' => null]);
        }
    }

    public function rules(): array
    {
        $imageMax = config('uploads.max_image_kb', 10240);
        $docMax = config('uploads.max_document_kb', 10240);

        return [
            'name' => 'required|string|max:255',
            'laboratory_name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'governorate_id' => 'required|integer|exists:governorates,id',
            'area' => 'required|string|max:255',
            'address' => 'required|string|max:1000',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'description_ar' => 'nullable|string|max:2000',
            'logo' => "required|image|mimes:jpg,jpeg,png|max:{$imageMax}",
            'commercial_register_document' => "required|file|mimes:pdf,jpg,jpeg,png|max:{$docMax}",
            'license_document' => "required|file|mimes:pdf,jpg,jpeg,png|max:{$docMax}",
            'owner_id_document' => "required|file|mimes:pdf,jpg,jpeg,png|max:{$docMax}",
            'accreditation_document' => "nullable|file|mimes:pdf,jpg,jpeg,png|max:{$docMax}",
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('phone')) {
                return;
            }

            try {
                PhoneNormalizer::toE164((string) $this->input('phone'));
            } catch (InvalidArgumentException $e) {
                $validator->errors()->add('phone', $e->getMessage());
            }
        });
    }

    public function messages(): array
    {
        $maxMb = (int) (config('uploads.max_image_kb', 10240) / 1024);

        return [
            'name.required' => 'اسم المسؤول مطلوب',
            'laboratory_name.required' => 'اسم المختبر مطلوب',
            'phone.unique' => 'رقم الهاتف مسجل بالفعل',
            'email.unique' => 'البريد الإلكتروني مسجل بالفعل',
            'password.confirmed' => 'كلمات المرور غير متطابقة',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'governorate_id.required' => 'المحافظة مطلوبة',
            'area.required' => 'المنطقة مطلوبة',
            'address.required' => 'عنوان المختبر مطلوب',
            'latitude.required' => 'موقع المختبر (خط العرض) مطلوب',
            'longitude.required' => 'موقع المختبر (خط الطول) مطلوب',
            'logo.required' => 'شعار المختبر مطلوب',
            'logo.image' => 'شعار المختبر يجب أن يكون صورة',
            'logo.max' => "حجم الشعار يجب أن يكون أقل من {$maxMb} ميجا",
            'commercial_register_document.required' => 'السجل التجاري مطلوب',
            'license_document.required' => 'ترخيص المختبر مطلوب',
            'owner_id_document.required' => 'هوية المالك مطلوبة',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم المسؤول',
            'laboratory_name' => 'اسم المختبر',
            'phone' => 'رقم الهاتف',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'governorate_id' => 'المحافظة',
            'area' => 'المنطقة',
            'address' => 'العنوان',
            'logo' => 'شعار المختبر',
            'commercial_register_document' => 'السجل التجاري',
            'license_document' => 'ترخيص المختبر',
            'owner_id_document' => 'هوية المالك',
            'accreditation_document' => 'شهادة الاعتماد',
        ];
    }
}
