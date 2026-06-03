<?php

namespace Modules\Subscription\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class SubscriptionServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->registerRoutes();
        $this->registerMigrations();
    }

    protected function registerRoutes()
    {
        Route::middleware('api')
            ->group(function () {
                require base_path('Modules/Subscription/Routes/api.php');
            });
    }

    protected function registerMigrations()
    {
        $this->loadMigrationsFrom(
            base_path('Modules/Subscription/database/migrations')
        );
    }
}
