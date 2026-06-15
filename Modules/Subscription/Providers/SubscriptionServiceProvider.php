<?php

namespace Modules\Subscription\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SubscriptionServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Routes are registered via routes/api.php and routes/dashboard.php
        $this->loadMigrationsFrom(
            base_path('Modules/Subscription/database/migrations')
        );
    }
}
