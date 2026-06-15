<?php

namespace Modules\Appointment\Http\Requests\Api;

use App\Http\Requests\ApiFormRequest;

class RescheduleAppointmentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'schedule_id'      => 'required|exists:doctor_schedules,id',
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_date.required'        => 'تاريخ الموعد مطلوب',
            'appointment_date.after_or_equal'  => 'تاريخ الموعد يجب أن يكون اليوم أو في المستقبل',
            'appointment_time.required'        => 'وقت الموعد مطلوب',
            'appointment_time.date_format'     => 'صيغة الوقت غير صحيحة (HH:MM)',
            'schedule_id.required'             => 'جدول الطبيب مطلوب',
            'schedule_id.exists'               => 'جدول الطبيب غير موجود',
        ];
    }
}
