<?php

namespace Modules\Review\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ReviewServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Routes are registered via routes/api.php
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
