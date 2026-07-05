<?php

namespace Modules\Pharmacy\Providers;

use Illuminate\Support\ServiceProvider;

class PharmacyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Modules\Pharmacy\Services\Web\PharmacyAuthService::class);
        $this->app->singleton(\Modules\Pharmacy\Services\Web\PharmacyProfileService::class);
        $this->app->singleton(\Modules\Pharmacy\Services\Web\PharmacyBranchService::class);
        $this->app->singleton(\Modules\Pharmacy\Services\Web\PharmacySubscriptionService::class);
        $this->app->singleton(\Modules\Pharmacy\Services\Web\PharmacyMedicineService::class);
        $this->app->singleton(\Modules\Pharmacy\Services\Web\PharmacyOrderWebService::class);
        $this->app->singleton(\Modules\Pharmacy\Services\Web\PharmacyMetricsWebService::class);
        $this->app->singleton(\Modules\Pharmacy\Services\Api\PharmacyService::class);
        $this->app->singleton(\Modules\Pharmacy\Services\Api\PharmacyOrderService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(base_path('Modules/Pharmacy/Routes/pharmacy.php'));
    }
}
