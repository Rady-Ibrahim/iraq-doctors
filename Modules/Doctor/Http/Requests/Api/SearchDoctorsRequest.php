<?php

namespace Modules\Doctor\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;

class SearchDoctorsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'speciality_id' => 'nullable|integer|exists:specialities,id',
            'name' => 'nullable|string|max:255',
            'min_rating' => 'nullable|numeric|min:0|max:5',
            'max_rating' => 'nullable|numeric|min:0|max:5',
            'min_fee' => 'nullable|numeric|min:0',
            'max_fee' => 'nullable|numeric|min:0',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'distance_range' => 'nullable|integer|in:5,10,20,50',
            'governorate' => 'nullable|string|max:255',
            'availability' => 'nullable|in:today,tomorrow,this_week',
            'consultation_type' => 'nullable|in:clinic,home,online',
            'experience_level' => 'nullable|in:junior,intermediate,senior',
            'sort_by' => 'nullable|in:rating,fee_asc,fee_desc,experience,distance',
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
