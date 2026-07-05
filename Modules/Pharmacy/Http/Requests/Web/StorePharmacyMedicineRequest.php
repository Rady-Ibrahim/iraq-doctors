<?php

namespace Modules\Pharmacy\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Pharmacy\Models\Pharmacy;

class StorePharmacyMedicineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pharmacyId = optional(
            Pharmacy::where('user_id', auth('web')->id())->first()
        )->id;

        return [
            'medicine_id' => [
                'required',
                'integer',
                'exists:medicines,id',
                Rule::unique('pharmacy_medicines', 'medicine_id')->where('pharmacy_id', $pharmacyId),
            ],
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0|max:999999',
            'expiry_date' => 'nullable|date|after_or_equal:today',
            'is_available' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function attributes(): array
    {
        return [
            'medicine_id' => 'الدواء',
            'price' => 'السعر',
            'stock_quantity' => 'الكمية في المخزون',
            'expiry_date' => 'تاريخ انتهاء الصلاحية',
            'notes' => 'ملاحظات',
        ];
    }

    public function messages(): array
    {
        return [
            'medicine_id.required' => 'يجب اختيار دواء',
            'medicine_id.exists' => 'الدواء غير صالح',
            'medicine_id.unique' => 'هذا الدواء مُضاف بالفعل لصيدليتك',
            'price.required' => 'السعر مطلوب',
            'price.numeric' => 'السعر يجب أن يكون رقماً',
            'price.min' => 'السعر لا يمكن أن يكون سالباً',
            'stock_quantity.required' => 'كمية المخزون مطلوبة',
            'stock_quantity.min' => 'كمية المخزون لا يمكن أن تكون سالبة',
        ];
    }
}
