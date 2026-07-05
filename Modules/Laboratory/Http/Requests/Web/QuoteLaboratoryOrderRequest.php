<?php

namespace Modules\Laboratory\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class QuoteLaboratoryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.laboratory_test_item_id' => 'required|integer|exists:laboratory_test_items,id',
            'items.*.quantity' => 'nullable|integer|min:1|max:99',
            'home_collection_fee' => 'nullable|numeric|min:0',
            'quote_notes' => 'nullable|string|max:2000',
            'scheduled_at' => 'nullable|date|after:now',
        ];
    }

    public function attributes(): array
    {
        return [
            'items' => 'التحاليل',
            'items.*.laboratory_test_item_id' => 'التحليل',
            'items.*.quantity' => 'الكمية',
            'home_collection_fee' => 'رسوم السحب من المنزل',
            'quote_notes' => 'ملاحظات العرض',
            'scheduled_at' => 'موعد السحب',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'يجب اختيار تحليل واحد على الأقل',
            'items.min' => 'يجب اختيار تحليل واحد على الأقل',
            'items.*.laboratory_test_item_id.required' => 'يجب اختيار التحليل',
            'items.*.laboratory_test_item_id.exists' => 'التحليل غير صالح',
            'home_collection_fee.min' => 'رسوم السحب لا يمكن أن تكون سالبة',
            'scheduled_at.after' => 'موعد السحب يجب أن يكون في المستقبل',
        ];
    }
}
