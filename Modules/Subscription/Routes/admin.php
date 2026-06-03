<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscription\Http\Controllers\Api\AdminSubscriptionController;

// Admin Subscription Management Routes
Route::get('/', [AdminSubscriptionController::class, 'index']);
Route::get('/stats', [AdminSubscriptionController::class, 'stats']);
Route::post('/', [AdminSubscriptionController::class, 'store']);
Route::put('/{id}', [AdminSubscriptionController::class, 'update']);
Route::delete('/{id}', [AdminSubscriptionController::class, 'destroy']);
