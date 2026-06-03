<?php

namespace Modules\MedicalRecord\Providers;

use Illuminate\Support\ServiceProvider;

class MedicalRecordServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}