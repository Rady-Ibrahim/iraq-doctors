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
            'appointment_id' => 'nullable|integer|exists:appointments,id|required_without_all:pharmacy_order_id,laboratory_order_id',
            'pharmacy_order_id' => 'nullable|integer|exists:pharmacy_orders,id|required_without_all:appointment_id,laboratory_order_id',
            'laboratory_order_id' => 'nullable|integer|exists:laboratory_orders,id|required_without_all:appointment_id,pharmacy_order_id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_id.exists' => 'الموعد غير موجود',
            'pharmacy_order_id.exists' => 'طلب الصيدلية غير موجود',
            'laboratory_order_id.exists' => 'طلب المختبر غير موجود',
            'appointment_id.required_without_all' => 'يجب تحديد موعد أو طلب صيدلية أو طلب مختبر',
            'pharmacy_order_id.required_without_all' => 'يجب تحديد موعد أو طلب صيدلية أو طلب مختبر',
            'laboratory_order_id.required_without_all' => 'يجب تحديد موعد أو طلب صيدلية أو طلب مختبر',
            'rating.min' => 'التقييم يجب أن يكون 1 على الأقل',
            'rating.max' => 'التقييم يجب أن يكون 5 على الأكثر',
        ];
    }
}
