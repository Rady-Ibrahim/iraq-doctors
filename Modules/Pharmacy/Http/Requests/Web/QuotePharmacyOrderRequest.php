<?php

namespace Modules\Pharmacy\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class QuotePharmacyOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.pharmacy_medicine_id' => 'required|integer|exists:pharmacy_medicines,id',
            'items.*.quantity' => 'nullable|integer|min:1|max:99',
            'delivery_fee' => 'nullable|numeric|min:0',
            'quote_notes' => 'nullable|string|max:2000',
        ];
    }

    public function attributes(): array
    {
        return [
            'items' => 'الأدوية',
            'items.*.pharmacy_medicine_id' => 'الدواء',
            'items.*.quantity' => 'الكمية',
            'delivery_fee' => 'رسوم التوصيل',
            'quote_notes' => 'ملاحظات العرض',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'يجب اختيار دواء واحد على الأقل',
            'items.min' => 'يجب اختيار دواء واحد على الأقل',
            'items.*.pharmacy_medicine_id.required' => 'يجب اختيار الدواء',
            'items.*.pharmacy_medicine_id.exists' => 'الدواء غير صالح',
            'delivery_fee.min' => 'رسوم التوصيل لا يمكن أن تكون سالبة',
        ];
    }
}
