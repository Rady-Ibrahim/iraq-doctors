<?php

namespace App\Notifications;

use App\Traits\SendsPushNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Appointment\Models\Appointment;

class AppointmentStatusChanged extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(
        private Appointment $appointment,
        private string $statusLabel,
        private string $type
    ) {}

    public function via(object $notifiable): array
    {
        return $this->patientPushChannels();
    }

    public function toArray(object $notifiable): array
    {
        $appointment = $this->appointment->loadMissing(['doctor.user']);

        return [
            'title' => 'تحديث الموعد',
            'message' => 'تم ' . $this->statusLabel . ' موعدك مع د.'
                . ($appointment->doctor?->user?->name ?? 'الطبيب')
                . ' بتاريخ ' . $appointment->appointment_date?->format('Y-m-d'),
            'appointment_id' => $appointment->id,
            'doctor_id' => $appointment->doctor_id,
            'status' => $appointment->status,
            'type' => $this->type,
            'action_url' => '/appointments/' . $appointment->id,
        ];
    }
}
