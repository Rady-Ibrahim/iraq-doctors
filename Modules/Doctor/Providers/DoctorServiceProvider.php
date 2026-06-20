<?php

namespace Modules\Doctor\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class DoctorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Modules\Doctor\Services\Web\DoctorAuthService::class);
    }

    public function boot(): void
    {
        // Routes are registered via routes/api.php and routes/dashboard.php
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(base_path('Modules/Doctor/Routes/doctor.php'));

    }
}
