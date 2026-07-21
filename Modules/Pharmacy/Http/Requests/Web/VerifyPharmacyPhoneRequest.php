<?php

namespace Modules\Pharmacy\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPharmacyPhoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'أدخل كود التحقق المكون من 6 أرقام',
            'code.size' => 'كود التحقق يجب أن يكون 6 أرقام',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'كود التحقق',
        ];
    }
}
