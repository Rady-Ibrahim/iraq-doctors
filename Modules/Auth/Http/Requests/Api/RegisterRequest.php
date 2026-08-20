<?php

namespace Modules\Auth\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;
use App\Support\PhoneNormalizer;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class RegisterRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:255',
            'phone' => 'required|string|unique:users,phone',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->filled('phone')) {
            return;
        }

        try {
            $this->merge([
                'phone' => PhoneNormalizer::toE164((string) $this->input('phone')),
            ]);
        } catch (InvalidArgumentException) {
            // validated in withValidator
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('phone')) {
                return;
            }

            try {
                PhoneNormalizer::toE164((string) $this->input('phone'));
            } catch (InvalidArgumentException $e) {
                $validator->errors()->add('phone', $e->getMessage());
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.min' => 'الاسم يجب أن يكون حرفين على الأقل',
            'phone.unique' => 'رقم الهاتف مسجل بالفعل',
            'password.confirmed' => 'كلمات المرور غير متطابقة',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
        ];
    }
}
