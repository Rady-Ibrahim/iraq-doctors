<?php

namespace App\Http\Middleware;

use App\Support\DoctorDashboardContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ShareDoctorDashboardContext
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $context = DoctorDashboardContext::make();
            app()->instance(DoctorDashboardContext::class, $context);
            View::share('doctorDashboard', $context);
        } catch (\Throwable) {
            // Context is resolved again in downstream middleware when needed.
        }

        return $next($request);
    }
}
