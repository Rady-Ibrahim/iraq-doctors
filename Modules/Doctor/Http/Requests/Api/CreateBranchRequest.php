<?php

namespace Modules\Doctor\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;

class CreateBranchRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_name' => 'required|string|max:255',
            'governorate' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:20',
            'is_primary' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'branch_name.required' => 'اسم الفرع مطلوب',
            'latitude.between' => 'خط العرض غير صحيح',
            'longitude.between' => 'خط الطول غير صحيح',
        ];
    }
}
