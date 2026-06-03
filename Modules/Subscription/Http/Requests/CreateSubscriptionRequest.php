<?php

namespace Modules\Subscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSubscriptionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'max_appointments' => 'nullable|integer|min:0',
            'is_featured' => 'boolean',
            'has_analytics' => 'boolean',
            'has_banner' => 'boolean',
            'visibility_score' => 'integer|min:1|max:3',
            'features' => 'nullable|array',
            'status' => 'in:active,inactive',
            'sort_order' => 'integer|min:0',
        ];
    }
}
