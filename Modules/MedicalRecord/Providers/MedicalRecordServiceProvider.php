<?php

namespace Modules\MedicalRecord\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MedicalRecordServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Modules\MedicalRecord\Services\Api\MedicalRecordService::class);
    }

    public function boot(): void
    {
        // Routes are registered via routes/api.php
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
