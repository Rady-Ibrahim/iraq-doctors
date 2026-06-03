<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
| Dashboard routes for Admin and Doctor dashboards
| Not under /api/v1 prefix - these are internal dashboard endpoints
|
*/

// Admin Dashboard Routes (Require admin role)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/dashboard')->group(function () {
    require __DIR__.'/../Modules/Admin/Routes/admin.php';
});

// Auth Admin User Management (Require admin role)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/users')->group(function () {
    require __DIR__.'/../Modules/Auth/Routes/admin.php';
});

// Doctor Dashboard Routes (Require doctor role)
Route::middleware(['auth:sanctum', 'doctor'])->prefix('doctor/dashboard')->group(function () {
    require __DIR__.'/../Modules/Doctor/Routes/doctor.php';
});

// Admin Subscription Management (Require admin role)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/subscriptions')->group(function () {
    require __DIR__.'/../Modules/Subscription/Routes/admin.php';
});
