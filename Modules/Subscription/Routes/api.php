<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Http\Controllers\Api\SubscriptionController;
use Modules\Subscription\Http\Controllers\Api\AdminSubscriptionController;

// Public routes - Get plans
Route::get('/subscriptions/plans', [SubscriptionController::class, 'index']);
Route::get('/subscriptions/plans/{id}', [SubscriptionController::class, 'show']);

// Doctor subscription routes (Require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Doctor subscription management
    Route::post('/subscriptions/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::get('/subscriptions/my', [SubscriptionController::class, 'mySubscription']);
    Route::post('/subscriptions/renew', [SubscriptionController::class, 'renew']);
    Route::post('/subscriptions/cancel', [SubscriptionController::class, 'cancel']);
    Route::get('/subscriptions/check-limit', [SubscriptionController::class, 'checkLimit']);
});

// Admin subscription management routes
// These are handled in dashboard.php
