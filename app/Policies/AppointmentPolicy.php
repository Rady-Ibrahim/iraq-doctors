<?php

namespace App\Policies;

use Modules\Auth\Models\User;
use Modules\Appointment\Models\Appointment;

class AppointmentPolicy
{
    public function view(User $authUser, Appointment $appointment): bool
    {
        return $authUser->isAdmin() || 
               ($authUser->isPatient() && $appointment->patient_id === $authUser->id) ||
               ($authUser->isDoctor() && $appointment->doctor_id === $authUser->doctor->id);
    }

    public function update(User $authUser, Appointment $appointment): bool
    {
        return $authUser->isAdmin() || 
               ($authUser->isDoctor() && $appointment->doctor_id === $authUser->doctor->id);
    }

    public function delete(User $authUser, Appointment $appointment): bool
    {
        return $authUser->isAdmin() || 
               ($authUser->isDoctor() && $appointment->doctor_id === $authUser->doctor->id) ||
               ($authUser->isPatient() && $appointment->patient_id === $authUser->id);
    }

    public function book(User $authUser): bool
    {
        return $authUser->isPatient();
    }

    public function confirm(User $authUser, Appointment $appointment): bool
    {
        return $authUser->isAdmin() || 
               ($authUser->isDoctor() && $appointment->doctor_id === $authUser->doctor->id);
    }

    public function cancel(User $authUser, Appointment $appointment): bool
    {
        return $authUser->isAdmin() || 
               ($authUser->isDoctor() && $appointment->doctor_id === $authUser->doctor->id) ||
               ($authUser->isPatient() && $appointment->patient_id === $authUser->id);
    }
}
