<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Auth\Models\User;
use Modules\Doctor\Models\Doctor;
use Modules\Appointment\Models\Appointment;
use App\Policies\UserPolicy;
use App\Policies\DoctorPolicy;
use App\Policies\AppointmentPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Doctor::class => DoctorPolicy::class,
        Appointment::class => AppointmentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
