<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Appointment\Models\Appointment;

class NewAppointmentBooked extends Notification
{
    use Queueable;

    public function __construct(private Appointment $appointment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $appointment = $this->appointment->loadMissing('patient');

        return [
            'title' => 'حجز جديد',
            'message' => 'تم حجز موعد جديد من ' . ($appointment->patient?->name ?? 'مريض'),
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'patient_name' => $appointment->patient?->name,
            'appointment_date' => $appointment->appointment_date?->format('Y-m-d'),
            'appointment_time' => $appointment->appointment_time,
            'type' => 'new_appointment',
            'action_url' => '/doctor/dashboard/requests',
        ];
    }
}
