<?php

namespace Modules\Pharmacy\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;

class CreatePharmacyOrderRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->items)) {
            $decoded = json_decode($this->items, true);
            if (is_array($decoded)) {
                $this->merge(['items' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        $imageMax = config('uploads.max_image_kb', 10240);
        $docMax = config('uploads.max_document_kb', 10240);
        $maxKb = max($imageMax, $docMax);

        return [
            'pharmacy_id' => 'required|integer|exists:pharmacies,id',
            'pharmacy_branch_id' => 'nullable|integer|exists:pharmacy_branches,id',
            'fulfillment_type' => 'required|in:pickup,delivery',
            'delivery_address' => 'nullable|required_if:fulfillment_type,delivery|string|max:1000',
            'delivery_latitude' => 'nullable|numeric|between:-90,90',
            'delivery_longitude' => 'nullable|numeric|between:-180,180',
            'delivery_notes' => 'nullable|string|max:500',
            'items' => 'nullable|array',
            'items.*.pharmacy_medicine_id' => 'required_with:items|integer|exists:pharmacy_medicines,id',
            'items.*.quantity' => 'nullable|integer|min:1|max:99',
            'prescription_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:' . $maxKb,
            'patient_notes' => 'nullable|string|max:2000',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasItems = is_array($this->items) && count($this->items) > 0;
            $hasImage = $this->hasFile('prescription_image');

            if (! $hasItems && ! $hasImage) {
                $validator->errors()->add('items', 'يجب اختيار دواء واحد على الأقل أو إرفاق صورة روشتة');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'pharmacy_id' => 'الصيدلية',
            'pharmacy_branch_id' => 'الفرع',
            'fulfillment_type' => 'طريقة الاستلام',
            'delivery_address' => 'عنوان التوصيل',
            'delivery_latitude' => 'خط عرض التوصيل',
            'delivery_longitude' => 'خط طول التوصيل',
            'delivery_notes' => 'ملاحظات التوصيل',
            'items' => 'الأدوية',
            'items.*.pharmacy_medicine_id' => 'الدواء',
            'prescription_image' => 'صورة الروشتة',
            'patient_notes' => 'ملاحظات',
        ];
    }

    public function messages(): array
    {
        return [
            'pharmacy_id.required' => 'يجب اختيار الصيدلية',
            'pharmacy_id.exists' => 'الصيدلية غير موجودة',
            'fulfillment_type.required' => 'يجب اختيار طريقة الاستلام (استلام أو توصيل)',
            'fulfillment_type.in' => 'طريقة الاستلام غير صالحة',
            'delivery_address.required_if' => 'عنوان التوصيل مطلوب عند اختيار التوصيل',
            'prescription_image.mimes' => 'صورة الروشتة يجب أن تكون صورة أو PDF',
            'prescription_image.max' => 'حجم صورة الروشتة كبير جداً',
        ];
    }
}
