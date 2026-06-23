<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Doctor\Models\Doctor;

class NewDoctorRegistered extends Notification
{
    use Queueable;

    public function __construct(private Doctor $doctor) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->doctor->loadMissing('user', 'speciality');

        return [
            'title' => 'طبيب جديد',
            'message' => 'سجّل د. ' . ($this->doctor->user?->name ?? 'غير معروف') . ' وينتظر الموافقة',
            'type' => 'new_doctor',
            'doctor_id' => $this->doctor->id,
            'doctor_name' => $this->doctor->user?->name,
            'speciality' => $this->doctor->speciality?->name_ar,
            'action_url' => '/admin/dashboard/doctors/' . $this->doctor->id,
        ];
    }
}
