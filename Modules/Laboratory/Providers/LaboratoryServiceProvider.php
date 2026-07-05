<?php

namespace Modules\Laboratory\Providers;

use Illuminate\Support\ServiceProvider;

class LaboratoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Modules\Laboratory\Services\Web\LaboratoryAuthService::class);
        $this->app->singleton(\Modules\Laboratory\Services\Web\LaboratoryProfileService::class);
        $this->app->singleton(\Modules\Laboratory\Services\Web\LaboratoryBranchService::class);
        $this->app->singleton(\Modules\Laboratory\Services\Web\LaboratorySubscriptionService::class);
        $this->app->singleton(\Modules\Laboratory\Services\Web\LaboratoryTestService::class);
        $this->app->singleton(\Modules\Laboratory\Services\Web\LaboratoryOrderWebService::class);
        $this->app->singleton(\Modules\Laboratory\Services\Web\LaboratoryResultWebService::class);
        $this->app->singleton(\Modules\Laboratory\Services\Web\LaboratoryMetricsWebService::class);
        $this->app->singleton(\Modules\Laboratory\Services\Api\LaboratoryService::class);
        $this->app->singleton(\Modules\Laboratory\Services\Api\LaboratoryOrderService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(base_path('Modules/Laboratory/Routes/laboratory.php'));
    }
}
