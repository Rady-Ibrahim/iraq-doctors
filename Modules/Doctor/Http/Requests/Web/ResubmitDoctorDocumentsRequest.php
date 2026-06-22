<?php

namespace Modules\Doctor\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class ResubmitDoctorDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check() && auth('web')->user()->isDoctor();
    }

    public function rules(): array
    {
        $docMax = config('uploads.max_document_kb', 10240);

        return [
            'license_document' => "required|file|mimes:pdf,jpg,jpeg,png|max:{$docMax}",
            'clinic_image' => "nullable|file|mimes:jpg,jpeg,png|max:{$docMax}",
        ];
    }

    public function messages(): array
    {
        return [
            'license_document.required' => 'يجب رفع رخصة مزاولة المهنة',
            'license_document.mimes' => 'صيغة الرخصة غير مدعومة',
        ];
    }
}
