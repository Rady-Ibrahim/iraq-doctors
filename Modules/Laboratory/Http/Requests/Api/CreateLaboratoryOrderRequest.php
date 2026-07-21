<?php

namespace Modules\Laboratory\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;

class CreateLaboratoryOrderRequest extends ApiFormRequest
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
            'laboratory_id'              => 'required|integer|exists:laboratories,id',
            'laboratory_branch_id'        => 'nullable|integer|exists:laboratory_branches,id',
            'collection_type'             => 'required|in:clinic,home',
            'patient_address'             => 'nullable|required_if:collection_type,home|string|max:1000',
            'patient_latitude'            => 'nullable|numeric|between:-90,90',
            'patient_longitude'           => 'nullable|numeric|between:-180,180',
            'items'                       => 'nullable|array',
            'items.*.laboratory_test_item_id' => 'required_with:items|integer|exists:laboratory_test_items,id',
            'items.*.quantity'            => 'nullable|integer|min:1|max:99',
            'prescription_image'          => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:' . $maxKb,
            'prescription_id'             => 'nullable|integer|exists:medical_records,id',
            'patient_notes'               => 'nullable|string|max:2000',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasItems = is_array($this->items) && count($this->items) > 0;
            $hasImage = $this->hasFile('prescription_image');
            $hasPrescription = $this->filled('prescription_id');

            if (! $hasItems && ! $hasImage && ! $hasPrescription) {
                $validator->errors()->add('items', 'يجب اختيار تحليل واحد على الأقل أو إرفاق صورة روشتة أو ربط روشتة الطبيب');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'laboratory_id'       => 'المختبر',
            'laboratory_branch_id'=> 'الفرع',
            'collection_type'     => 'نوع الجمع',
            'patient_address'     => 'عنوان المريض',
            'patient_latitude'    => 'خط العرض',
            'patient_longitude'   => 'خط الطول',
            'items'               => 'التحاليل',
            'items.*.laboratory_test_item_id' => 'التحليل',
            'prescription_image'  => 'صورة الروشتة',
            'patient_notes'       => 'ملاحظات',
        ];
    }

    public function messages(): array
    {
        return [
            'laboratory_id.required'         => 'يجب اختيار المختبر',
            'laboratory_id.exists'           => 'المختبر غير موجود',
            'collection_type.required'       => 'يجب اختيار طريقة الجمع (عيادة أو منزل)',
            'collection_type.in'             => 'طريقة الجمع غير صالحة',
            'patient_address.required_if'    => 'عنوان المريض مطلوب عند اختيار الجمع المنزلي',
            'prescription_image.mimes'       => 'صورة الروشتة يجب أن تكون صورة أو PDF',
            'prescription_image.max'         => 'حجم صورة الروشتة كبير جداً',
        ];
    }
}
