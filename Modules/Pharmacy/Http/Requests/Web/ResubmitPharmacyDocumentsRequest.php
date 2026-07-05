<?php

namespace Modules\Pharmacy\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class ResubmitPharmacyDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check() && auth('web')->user()->isPharmacy();
    }

    public function rules(): array
    {
        $docMax = config('uploads.max_document_kb', 10240);
        $imageMax = config('uploads.max_image_kb', 10240);

        return [
            'commercial_register_document' => "required|file|mimes:pdf,jpg,jpeg,png|max:{$docMax}",
            'license_document' => "required|file|mimes:pdf,jpg,jpeg,png|max:{$docMax}",
            'owner_id_document' => "required|file|mimes:pdf,jpg,jpeg,png|max:{$docMax}",
            'logo' => "nullable|image|mimes:jpg,jpeg,png|max:{$imageMax}",
        ];
    }

    public function messages(): array
    {
        return [
            'commercial_register_document.required' => 'يجب رفع السجل التجاري',
            'license_document.required' => 'يجب رفع ترخيص الصيدلية',
            'owner_id_document.required' => 'يجب رفع هوية المالك',
        ];
    }

    public function attributes(): array
    {
        return [
            'commercial_register_document' => 'السجل التجاري',
            'license_document' => 'ترخيص الصيدلية',
            'owner_id_document' => 'هوية المالك',
            'logo' => 'شعار الصيدلية',
        ];
    }
}
