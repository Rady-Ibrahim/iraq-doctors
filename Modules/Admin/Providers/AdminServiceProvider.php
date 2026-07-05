<?php

namespace Modules\Admin\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Modules\Admin\Services\AdminOrdersService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(base_path('Modules/Admin/Routes/admin.php'));
    }
}