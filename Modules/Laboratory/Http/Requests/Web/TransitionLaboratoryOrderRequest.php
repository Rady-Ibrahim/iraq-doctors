<?php

namespace Modules\Laboratory\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Laboratory\Enums\LaboratoryOrderStatus;

class TransitionLaboratoryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statuses = array_map(fn (LaboratoryOrderStatus $s) => $s->value, LaboratoryOrderStatus::cases());

        return [
            'status' => ['required', 'string', Rule::in($statuses)],
            'cancel_reason' => 'nullable|required_if:status,cancelled|string|max:1000',
            'scheduled_at' => 'nullable|required_if:status,scheduled|date',
            'lab_notes' => 'nullable|string|max:2000',
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'الحالة',
            'cancel_reason' => 'سبب الإلغاء',
            'scheduled_at' => 'موعد السحب',
            'lab_notes' => 'ملاحظات المعمل',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'الحالة مطلوبة',
            'status.in' => 'حالة الطلب غير صالحة',
            'cancel_reason.required_if' => 'سبب الإلغاء مطلوب',
            'scheduled_at.required_if' => 'موعد السحب مطلوب عند الجدولة',
        ];
    }
}
