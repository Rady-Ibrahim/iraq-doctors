<?php

namespace Modules\StaticPage\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class StaticPageServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Routes are registered via routes/api.php
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
