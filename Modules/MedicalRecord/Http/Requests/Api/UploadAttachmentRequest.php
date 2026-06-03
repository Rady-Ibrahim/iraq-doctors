<?php

namespace Modules\MedicalRecord\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UploadAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:jpeg,jpg,png,pdf|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'الملف مطلوب',
            'file.mimes' => 'يجب أن يكون الملف صورة أو PDF',
            'file.max' => 'حجم الملف يجب أن يكون أقل من 10 ميجابايت',
        ];
    }
}
