<?php

namespace Modules\Pharmacy\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Pharmacy\Enums\PharmacyOrderStatus;

class TransitionPharmacyOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statuses = array_map(fn (PharmacyOrderStatus $s) => $s->value, PharmacyOrderStatus::cases());

        return [
            'status' => ['required', 'string', Rule::in($statuses)],
            'cancel_reason' => 'nullable|required_if:status,cancelled|string|max:1000',
            'pharmacy_notes' => 'nullable|string|max:2000',
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'الحالة',
            'cancel_reason' => 'سبب الإلغاء',
            'pharmacy_notes' => 'ملاحظات الصيدلية',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'الحالة مطلوبة',
            'status.in' => 'حالة الطلب غير صالحة',
            'cancel_reason.required_if' => 'سبب الإلغاء مطلوب',
        ];
    }
}
