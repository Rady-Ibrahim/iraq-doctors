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
        $maxKb = config('uploads.max_image_kb', 10240);

        return [
            'avatar' => "required|image|mimes:jpeg,jpg,png,webp|max:{$maxKb}",
        ];
    }

    public function messages(): array
    {
        $maxMb = (int) (config('uploads.max_image_kb', 10240) / 1024);

        return [
            'avatar.required' => 'الصورة مطلوبة',
            'avatar.image'    => 'الملف يجب أن يكون صورة',
            'avatar.mimes'    => 'الصورة يجب أن تكون jpeg أو png أو webp',
            'avatar.max'      => "حجم الصورة يجب أن يكون أقل من {$maxMb} ميجا",
        ];
    }
}
