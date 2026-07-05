<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

// Rate limit for API requests
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

// Stricter rate limiting for authentication
RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

// Rate limiting for sensitive operations
RateLimiter::for('sensitive', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
});

// Laravel already prefixes this file with /api via RouteServiceProvider
Route::prefix('v1')->group(function () {
    require __DIR__.'/../Modules/Auth/Routes/api.php';
    require __DIR__.'/../Modules/Doctor/Routes/api.php';
    require __DIR__.'/../Modules/Appointment/Routes/api.php';
    require __DIR__.'/../Modules/Review/Routes/api.php';
    require __DIR__.'/../Modules/MedicalRecord/Routes/api.php';
    require __DIR__.'/../Modules/StaticPage/Routes/api.php';
    require __DIR__.'/../Modules/Subscription/Routes/api.php';
    require __DIR__.'/../Modules/Laboratory/Routes/api.php';
    require __DIR__.'/../Modules/Pharmacy/Routes/api.php';
});
