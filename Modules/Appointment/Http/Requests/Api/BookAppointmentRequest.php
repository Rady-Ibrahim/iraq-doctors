<?php

namespace Modules\Appointment\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;

class BookAppointmentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => 'required|integer|exists:doctors,id',
            'schedule_id' => 'nullable|integer|exists:doctor_schedules,id',
            'appointment_date' => 'required|date|after:today',
            'appointment_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'doctor_id.exists' => 'الطبيب غير موجود',
            'appointment_date.after' => 'تاريخ الموعد يجب أن يكون في المستقبل',
            'appointment_time.date_format' => 'وقت الموعد غير صحيح',
        ];
    }
}
