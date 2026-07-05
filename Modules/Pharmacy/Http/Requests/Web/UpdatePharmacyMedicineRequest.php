<?php

namespace Modules\Pharmacy\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePharmacyMedicineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price' => 'sometimes|required|numeric|min:0',
            'stock_quantity' => 'sometimes|required|integer|min:0|max:999999',
            'expiry_date' => 'nullable|date',
            'is_available' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function attributes(): array
    {
        return [
            'price' => 'السعر',
            'stock_quantity' => 'الكمية في المخزون',
            'notes' => 'ملاحظات',
        ];
    }

    public function messages(): array
    {
        return [
            'price.required' => 'السعر مطلوب',
            'price.numeric' => 'السعر يجب أن يكون رقماً',
            'price.min' => 'السعر لا يمكن أن يكون سالباً',
            'stock_quantity.required' => 'كمية المخزون مطلوبة',
            'stock_quantity.min' => 'كمية المخزون لا يمكن أن تكون سالبة',
        ];
    }
}
