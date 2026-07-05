<?php

namespace Modules\Laboratory\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreLaboratoryOrderResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $docMax = config('uploads.max_document_kb', 10240);
        $imageMax = config('uploads.max_image_kb', 10240);
        $maxKb = max($docMax, $imageMax);

        return [
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:' . $maxKb,
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'ملف النتيجة',
            'notes' => 'ملاحظات',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'ملف النتيجة مطلوب',
            'file.mimes' => 'يجب أن يكون الملف PDF أو صورة',
            'file.max' => 'حجم الملف كبير جداً',
        ];
    }
}
