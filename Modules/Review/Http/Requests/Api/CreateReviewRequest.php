<?php

namespace Modules\Review\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;

class CreateReviewRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => 'required|integer|exists:appointments,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_id.exists' => 'الموعد غير موجود',
            'rating.min' => 'التقييم يجب أن يكون 1 على الأقل',
            'rating.max' => 'التقييم يجب أن يكون 5 على الأكثر',
        ];
    }
}
