<?php

use Illuminate\Support\Facades\Route;
use Modules\Doctor\Http\Controllers\Doctor\DoctorDashboardController;

// Doctor Dashboard Routes
Route::get('/metrics', [DoctorDashboardController::class, 'metrics']);
Route::get('/patients', [DoctorDashboardController::class, 'patients']);
Route::get('/patients/{id}', [DoctorDashboardController::class, 'patientDetails']);
Route::get('/patients/{id}/prescriptions', [DoctorDashboardController::class, 'patientPrescriptions']);
Route::get('/today-activity', [DoctorDashboardController::class, 'todayActivity']);
Route::get('/upcoming-tasks', [DoctorDashboardController::class, 'upcomingTasks']);
Route::get('/prescriptions', [DoctorDashboardController::class, 'prescriptions']);
Route::get('/records', [DoctorDashboardController::class, 'records']);
