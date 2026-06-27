<?php

namespace Modules\Auth\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;

class FirebaseAuthRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firebase_token' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'name' => 'nullable|string|max:255',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $testMode = (bool) config('firebase.auth_test_mode') && !app()->environment('production');
            $testKey = (string) $this->header('X-Auth-Test-Key', '');
            $expectedKey = (string) config('firebase.auth_test_key');
            $isTestRequest = $testMode && $expectedKey !== '' && hash_equals($expectedKey, $testKey);

            if ($isTestRequest) {
                if (empty($this->phone)) {
                    $v->errors()->add('phone', 'رقم الهاتف مطلوب في وضع الاختبار');
                }

                return;
            }

            if (empty($this->firebase_token)) {
                $v->errors()->add('firebase_token', 'رمز Firebase مطلوب');
            }
        });
    }
}
