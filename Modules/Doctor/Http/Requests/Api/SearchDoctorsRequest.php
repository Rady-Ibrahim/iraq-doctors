<?php

namespace Modules\Doctor\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SearchDoctorsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'speciality_id' => 'nullable|uuid|exists:specialities,id',
            'name' => 'nullable|string|max:255',
            'min_rating' => 'nullable|numeric|min:0|max:5',
            'max_fee' => 'nullable|numeric|min:0',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0',
            'sort_by' => 'nullable|in:rating,fee_asc,fee_desc,experience',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'speciality_id.exists' => 'التخصص غير موجود',
            'min_rating.max' => 'التقييم يجب أن يكون بين 0 و 5',
            'latitude.between' => 'خط العرض غير صحيح',
            'longitude.between' => 'خط الطول غير صحيح',
        ];
    }
}
