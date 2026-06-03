<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\Api\AdminDashboardController;

// Admin Dashboard Routes
Route::get('/metrics', [AdminDashboardController::class, 'metrics']);
Route::get('/doctors', [AdminDashboardController::class, 'doctorsStats']);
Route::get('/patients', [AdminDashboardController::class, 'patientsStats']);
Route::get('/appointments', [AdminDashboardController::class, 'appointmentsStats']);
Route::get('/revenue', [AdminDashboardController::class, 'revenueStats']);
Route::get('/analytics', [AdminDashboardController::class, 'analytics']);
Route::post('/doctors/{id}/approve', [AdminDashboardController::class, 'approveDoctor']);
Route::post('/doctors/{id}/reject', [AdminDashboardController::class, 'rejectDoctor']);
Route::post('/doctors/{id}/suspend', [AdminDashboardController::class, 'suspendDoctor']);
Route::post('/doctors/{id}/activate', [AdminDashboardController::class, 'activateDoctor']);
