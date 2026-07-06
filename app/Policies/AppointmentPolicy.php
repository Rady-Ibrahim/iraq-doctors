<?php

namespace App\Policies;

use Modules\Auth\Models\User;
use Modules\Appointment\Models\Appointment;

class AppointmentPolicy
{
    public function view(User $authUser, Appointment $appointment): bool
    {
        return $authUser->isAdmin()
            || ($authUser->isPatient() && $appointment->patient_id === $authUser->id)
            || $authUser->canManageDoctor($appointment->doctor_id);
    }

    public function update(User $authUser, Appointment $appointment): bool
    {
        return $authUser->isAdmin()
            || ($authUser->canManageDoctor($appointment->doctor_id) && $authUser->hasDoctorPermission('appointments.manage'));
    }

    public function delete(User $authUser, Appointment $appointment): bool
    {
        return $authUser->isAdmin()
            || ($authUser->canManageDoctor($appointment->doctor_id) && $authUser->hasDoctorPermission('appointments.manage'))
            || ($authUser->isPatient() && $appointment->patient_id === $authUser->id);
    }

    public function book(User $authUser): bool
    {
        return $authUser->isPatient();
    }

    public function confirm(User $authUser, Appointment $appointment): bool
    {
        return $authUser->isAdmin()
            || ($authUser->canManageDoctor($appointment->doctor_id) && $authUser->hasDoctorPermission('appointments.manage'));
    }

    public function cancel(User $authUser, Appointment $appointment): bool
    {
        return $authUser->isAdmin()
            || ($authUser->canManageDoctor($appointment->doctor_id) && $authUser->hasDoctorPermission('appointments.manage'))
            || ($authUser->isPatient() && $appointment->patient_id === $authUser->id);
    }
}
