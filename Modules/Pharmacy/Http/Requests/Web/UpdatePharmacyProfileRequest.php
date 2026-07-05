<?php

namespace Modules\Pharmacy\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePharmacyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->working_hours)) {
            $decoded = json_decode($this->working_hours, true);
            if (is_array($decoded)) {
                $this->merge(['working_hours' => $decoded]);
            }
        }

        if ($this->has('delivery_enabled')) {
            $this->merge([
                'delivery_enabled' => filter_var($this->delivery_enabled, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    public function rules(): array
    {
        $imageMax = config('uploads.max_image_kb', 10240);

        return [
            'name' => 'sometimes|required|string|max:255',
            'description_ar' => 'nullable|string|max:2000',
            'governorate_id' => 'sometimes|required|integer|exists:governorates,id',
            'district' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'contact_phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'working_hours' => 'nullable|array',
            'working_hours.*.enabled' => 'nullable|boolean',
            'working_hours.*.open' => 'nullable|date_format:H:i',
            'working_hours.*.close' => 'nullable|date_format:H:i',
            'delivery_enabled' => 'nullable|boolean',
            'delivery_fee' => 'nullable|numeric|min:0',
            'min_order_for_delivery' => 'nullable|numeric|min:0',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:' . $imageMax,
            'user_name' => 'nullable|string|max:255',
            'user_email' => 'nullable|email|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم الصيدلية',
            'description_ar' => 'الوصف',
            'governorate_id' => 'المحافظة',
            'district' => 'المنطقة',
            'address' => 'العنوان',
            'latitude' => 'خط العرض',
            'longitude' => 'خط الطول',
            'contact_phone' => 'هاتف التواصل',
            'whatsapp' => 'واتساب',
            'working_hours' => 'ساعات العمل',
            'delivery_enabled' => 'خدمة التوصيل',
            'delivery_fee' => 'رسوم التوصيل',
            'min_order_for_delivery' => 'الحد الأدنى للتوصيل',
            'logo' => 'الشعار',
            'user_name' => 'اسم المسؤول',
            'user_email' => 'البريد الإلكتروني',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الصيدلية مطلوب',
            'governorate_id.required' => 'المحافظة مطلوبة',
            'governorate_id.exists' => 'المحافظة غير صالحة',
            'latitude.between' => 'خط العرض غير صحيح',
            'longitude.between' => 'خط الطول غير صحيح',
            'logo.image' => 'يجب أن يكون الشعار صورة',
            'logo.max' => 'حجم الشعار كبير جداً',
        ];
    }
}
