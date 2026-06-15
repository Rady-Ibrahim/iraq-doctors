<?php

namespace Modules\Auth\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;

class UploadAvatarRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => 'required|image|mimes:jpeg,jpg,png,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'الصورة مطلوبة',
            'avatar.image'    => 'الملف يجب أن يكون صورة',
            'avatar.mimes'    => 'الصورة يجب أن تكون jpeg أو png أو webp',
            'avatar.max'      => 'حجم الصورة يجب أن يكون أقل من 2 ميجا',
        ];
    }
}
