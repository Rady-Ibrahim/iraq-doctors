<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Api\AuthController;
use Modules\Auth\Http\Controllers\Api\DeviceController;
use Modules\Auth\Http\Controllers\Api\PatientController;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('send-otp', [AuthController::class, 'sendOtp'])->middleware('throttle:auth');
    Route::post('verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:auth');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::put('password', [AuthController::class, 'updatePassword']);
        Route::post('avatar', [AuthController::class, 'uploadAvatar']);

        Route::post('devices/register', [DeviceController::class, 'register']);
        Route::delete('devices/unregister', [DeviceController::class, 'unregister']);
        Route::delete('devices', [DeviceController::class, 'unregisterAll']);
    });
});

Route::middleware('auth:sanctum')->prefix('patient')->group(function () {
    Route::get('/orders', [PatientController::class, 'orders']);
    Route::get('/notifications', [PatientController::class, 'notifications']);
    Route::post('/notifications/{id}/read', [PatientController::class, 'markNotificationRead']);
    Route::post('/notifications/read-all', [PatientController::class, 'markAllNotificationsRead']);
});
