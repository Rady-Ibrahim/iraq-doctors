<?php

namespace App\Policies;

use Modules\Auth\Models\User;
use Modules\Doctor\Models\Doctor;

class DoctorPolicy
{
    public function view(User $authUser, Doctor $doctor): bool
    {
        return $authUser->isAdmin() || 
               ($authUser->isDoctor() && $authUser->doctor->id === $doctor->id);
    }

    public function update(User $authUser, Doctor $doctor): bool
    {
        return $authUser->isAdmin() || 
               ($authUser->isDoctor() && $authUser->doctor->id === $doctor->id);
    }

    public function delete(User $authUser, Doctor $doctor): bool
    {
        return $authUser->isAdmin();
    }

    public function approve(User $authUser): bool
    {
        return $authUser->isAdmin();
    }

    public function reject(User $authUser): bool
    {
        return $authUser->isAdmin();
    }

    public function manageSchedule(User $authUser, Doctor $doctor): bool
    {
        return $authUser->isAdmin() || 
               ($authUser->isDoctor() && $authUser->doctor->id === $doctor->id);
    }
}
