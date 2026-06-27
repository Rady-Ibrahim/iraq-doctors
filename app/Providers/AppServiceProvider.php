<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Services\FirebaseTokenVerifier;
use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FirebaseTokenVerifier::class, function ($app) {
            $auth = null;
            $credentials = config('firebase.credentials');

            if ($credentials) {
                $path = $app->basePath($credentials);

                if (is_file($path)) {
                    try {
                        $auth = (new Factory)->withServiceAccount($path)->createAuth();
                    } catch (\Throwable) {
                        $auth = null;
                    }
                }
            }

            return new FirebaseTokenVerifier($auth);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
