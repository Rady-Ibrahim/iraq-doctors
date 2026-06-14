<?php

namespace Modules\MedicalRecord\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;

class CreateMedicalRecordRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => 'required|uuid|exists:appointments,id',
            'record_type' => 'required|in:prescription,report,diagnosis',
            'diagnosis' => 'nullable|string',
            'prescription' => 'nullable|array',
            'prescription.*.name' => 'required|string',
            'prescription.*.dosage' => 'nullable|string',
            'prescription.*.frequency' => 'nullable|string',
            'prescription.*.duration' => 'nullable|string',
            'notes' => 'nullable|string|max:2000',
            'attachments' => 'nullable|array',
            'weight' => 'nullable|numeric|min:0|max:500',
            'height' => 'nullable|numeric|min:0|max:300',
            'blood_pressure' => 'nullable|string|max:20',
            'allergies' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_id.exists' => 'الموعد غير موجود',
            'record_type.in' => 'نوع السجل غير صحيح',
        ];
    }
}
