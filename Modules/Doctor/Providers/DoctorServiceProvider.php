<?php

namespace Modules\Doctor\Providers;

use App\Support\DoctorDashboardContext;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class DoctorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Modules\Doctor\Services\Web\DoctorAuthService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(base_path('Modules/Doctor/Routes/doctor.php'));

        View::composer('doctor.*', function ($view) {
            $user = auth('web')->user();
            $context = app()->bound(DoctorDashboardContext::class)
                ? app(DoctorDashboardContext::class)
                : null;

            $view->with([
                'doctorDashboard' => $context,
                'isDoctorOwner' => (bool) $user?->isDoctor(),
                'canDoctor' => function (string $permission) use ($user, $context) {
                    if ($user?->isDoctor()) {
                        return true;
                    }

                    return $context?->hasPermission($permission) ?? false;
                },
            ]);
        });
    }
}
