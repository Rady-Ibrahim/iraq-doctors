<?php

namespace App\Traits;

use App\Support\DoctorDashboardContext;
use Modules\Doctor\Models\Doctor;

trait ResolvesDoctorDashboard
{
    protected function dashboardContext(): DoctorDashboardContext
    {
        return DoctorDashboardContext::resolve();
    }

    protected function resolveDoctor(): Doctor
    {
        return $this->dashboardContext()->doctor;
    }
}
