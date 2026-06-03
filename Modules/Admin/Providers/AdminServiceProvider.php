<?php

namespace Modules\Admin\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AdminServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->registerRoutes();
    }

    protected function registerRoutes()
    {
        Route::middleware('api')
            ->group(function () {
                require base_path('Modules/Admin/Routes/admin.php');
            });
    }
}
