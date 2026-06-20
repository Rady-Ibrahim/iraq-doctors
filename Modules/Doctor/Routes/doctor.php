<?php

use Illuminate\Support\Facades\Route;
use Modules\Doctor\Http\Controllers\Doctor\DoctorDashboardController;
use Modules\Doctor\Http\Controllers\Web\DoctorAuthController;
use Modules\Doctor\Http\Controllers\Web\DoctorBranchController;

Route::middleware('web')->group(function () {
    Route::redirect('/', '/doctor/login');

    Route::prefix('doctor')->name('doctor.')->group(function () {
    Route::middleware('guest:web')->group(function () {
        Route::get('/login', [DoctorAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [DoctorAuthController::class, 'login']);
        Route::get('/register', [DoctorAuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [DoctorAuthController::class, 'register']);
    });

    Route::middleware(['auth:web', 'doctor'])->group(function () {
        Route::post('/logout', [DoctorAuthController::class, 'logout'])->name('logout');

        Route::get('/dashboard', fn () => view('doctor.dashboard'))->name('dashboard');
        Route::get('/dashboard/calendar', fn () => view('doctor.calendar'));
        Route::get('/dashboard/settings', fn () => view('doctor.settings'));
        Route::get('/dashboard/patients', fn () => view('doctor.patients.index'));
        Route::get('/dashboard/patients/{id}', fn () => view('doctor.patients.show'));
        Route::get('/dashboard/patients/{id}/records', fn () => view('doctor.patients.records'));
        Route::get('/dashboard/prescriptions', fn () => view('doctor.prescriptions.index'));
        Route::get('/dashboard/prescriptions/create', fn () => view('doctor.prescriptions.create'));
        Route::get('/dashboard/prescriptions/{id}', fn () => view('doctor.prescriptions.show'));
        Route::get('/dashboard/prescriptions/{id}/edit', fn () => view('doctor.prescriptions.edit'));
        Route::get('/dashboard/records', fn () => view('doctor.records.index'));
        Route::get('/dashboard/records/create', fn () => view('doctor.records.create'));
        Route::get('/dashboard/records/{id}', fn () => view('doctor.records.show'));
        Route::get('/dashboard/records/{id}/edit', fn () => view('doctor.records.edit'));

        Route::prefix('api')->group(function () {
            Route::get('/metrics', [DoctorDashboardController::class, 'metrics']);
            Route::get('/today-activity', [DoctorDashboardController::class, 'todayActivity']);
            Route::get('/upcoming-tasks', [DoctorDashboardController::class, 'upcomingTasks']);

            Route::get('/patients', [DoctorDashboardController::class, 'patients']);
            Route::get('/patients/{id}', [DoctorDashboardController::class, 'patientDetails']);
            Route::get('/patients/{id}/prescriptions', [DoctorDashboardController::class, 'patientPrescriptions']);
            Route::post('/ghost-patients', [DoctorDashboardController::class, 'createGhostPatient']);

            Route::get('/prescriptions', [DoctorDashboardController::class, 'prescriptions']);
            Route::post('/prescriptions', [DoctorDashboardController::class, 'storePrescription']);
            Route::get('/prescriptions/{id}', [DoctorDashboardController::class, 'showPrescription']);
            Route::put('/prescriptions/{id}', [DoctorDashboardController::class, 'updatePrescription']);
            Route::delete('/prescriptions/{id}', [DoctorDashboardController::class, 'destroyPrescription']);

            Route::get('/records', [DoctorDashboardController::class, 'records']);
            Route::post('/records', [DoctorDashboardController::class, 'storeRecord']);
            Route::get('/records/{id}', [DoctorDashboardController::class, 'showRecord']);
            Route::put('/records/{id}', [DoctorDashboardController::class, 'updateRecord']);
            Route::delete('/records/{id}', [DoctorDashboardController::class, 'destroyRecord']);

            Route::get('/profile', [DoctorDashboardController::class, 'profile']);
            Route::put('/profile', [DoctorDashboardController::class, 'updateProfile']);
            Route::put('/professional', [DoctorDashboardController::class, 'updateProfessional']);

            Route::get('/schedules', [DoctorDashboardController::class, 'schedules']);
            Route::delete('/schedules/{scheduleId}', [DoctorDashboardController::class, 'deleteSchedule']);

            Route::get('/calendar', [DoctorDashboardController::class, 'calendar']);
            Route::get('/appointments', [DoctorDashboardController::class, 'appointments']);
            Route::get('/appointments/{appointmentId}', [DoctorDashboardController::class, 'appointmentDetails']);

            Route::get('/subscription', [DoctorDashboardController::class, 'subscription']);
            Route::post('/change-password', [DoctorDashboardController::class, 'changePassword']);
            Route::get('/two-factor-status', [DoctorDashboardController::class, 'twoFactorStatus']);
            Route::post('/two-factor', [DoctorDashboardController::class, 'toggleTwoFactor']);

            Route::get('/branches', [DoctorBranchController::class, 'index']);
            Route::post('/branches', [DoctorBranchController::class, 'store']);
            Route::put('/branches/{branchId}', [DoctorBranchController::class, 'update']);
            Route::delete('/branches/{branchId}', [DoctorBranchController::class, 'destroy']);
        });
    });
});
});
