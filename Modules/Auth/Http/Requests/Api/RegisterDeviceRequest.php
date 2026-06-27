<?php

namespace Modules\Auth\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;

class RegisterDeviceRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'player_id' => 'required|string|max:255',
            'platform' => 'required|in:android,ios',
        ];
    }

    public function messages(): array
    {
        return [
            'player_id.required' => 'معرّف OneSignal مطلوب',
            'platform.in' => 'المنصة يجب أن تكون android أو ios',
        ];
    }
}
