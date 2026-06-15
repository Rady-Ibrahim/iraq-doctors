<?php

namespace Modules\Auth\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Modules\Auth\Services\Api\AuthService::class, function ($app) {
            return new \Modules\Auth\Services\Api\AuthService();
        });
    }

    public function boot(): void
    {
        // Routes are registered via routes/api.php and routes/dashboard.php
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
