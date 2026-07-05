<?php

namespace Modules\Laboratory\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeLaboratoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $docMax = config('uploads.max_document_kb', 10240);

        return [
            'subscription_id' => 'required|integer|exists:subscriptions,id',
            'submitted_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:vodafone_cash,bank_transfer',
            'payment_receipt' => 'required|file|mimes:jpg,jpeg,png,pdf|max:' . $docMax,
        ];
    }

    public function attributes(): array
    {
        return [
            'subscription_id' => 'الباقة',
            'submitted_amount' => 'المبلغ المُحوّل',
            'payment_method' => 'طريقة الدفع',
            'payment_receipt' => 'إيصال الدفع',
        ];
    }

    public function messages(): array
    {
        return [
            'subscription_id.required' => 'يجب اختيار باقة الاشتراك',
            'subscription_id.exists' => 'الباقة المختارة غير صالحة',
            'submitted_amount.required' => 'المبلغ المُحوّل مطلوب',
            'payment_method.required' => 'طريقة الدفع مطلوبة',
            'payment_method.in' => 'طريقة الدفع غير صالحة',
            'payment_receipt.required' => 'إيصال الدفع مطلوب',
            'payment_receipt.mimes' => 'إيصال الدفع يجب أن يكون صورة أو PDF',
            'payment_receipt.max' => 'حجم إيصال الدفع كبير جداً',
        ];
    }
}
