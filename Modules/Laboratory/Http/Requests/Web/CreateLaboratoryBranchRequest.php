<?php

namespace Modules\Laboratory\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class CreateLaboratoryBranchRequest extends FormRequest
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
    }

    public function rules(): array
    {
        return [
            'branch_name' => 'required|string|max:255',
            'governorate_id' => 'nullable|integer|exists:governorates,id',
            'district' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:20',
            'is_primary' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'working_hours' => 'nullable|array',
            'working_hours.*.enabled' => 'nullable|boolean',
            'working_hours.*.open' => 'nullable|date_format:H:i',
            'working_hours.*.close' => 'nullable|date_format:H:i',
        ];
    }

    public function attributes(): array
    {
        return [
            'branch_name' => 'اسم الفرع',
            'governorate_id' => 'المحافظة',
            'district' => 'المنطقة',
            'address' => 'العنوان',
            'latitude' => 'خط العرض',
            'longitude' => 'خط الطول',
            'phone' => 'الهاتف',
            'is_primary' => 'فرع رئيسي',
        ];
    }

    public function messages(): array
    {
        return [
            'branch_name.required' => 'اسم الفرع مطلوب',
            'governorate_id.exists' => 'المحافظة غير صالحة',
            'latitude.between' => 'خط العرض غير صحيح',
            'longitude.between' => 'خط الطول غير صحيح',
        ];
    }
}
