<?php

use Illuminate\Support\Facades\Route;
use Modules\Doctor\Http\Controllers\Doctor\DoctorDashboardController;

    Route::get('/',     fn() => view('doctor.login'))->name('doctor.login');


Route::prefix('doctor')->group(function () {
    Route::get('/dashboard', fn() => view('doctor.dashboard'));
    Route::get('/dashboard/calendar',  fn() => view('doctor.calendar'));
    Route::get('/dashboard/settings',  fn() => view('doctor.settings'));
    Route::get('/dashboard/patients',       fn() => view('doctor.patients.index'));
    Route::get('/dashboard/patients/{id}',          fn() => view('doctor.patients.show'));
    Route::get('/dashboard/patients/{id}/records',  fn() => view('doctor.patients.records'));
    Route::get('/dashboard/prescriptions',           fn() => view('doctor.prescriptions.index'));
    Route::get('/dashboard/prescriptions/create',    fn() => view('doctor.prescriptions.create'));
    Route::get('/dashboard/prescriptions/{id}',      fn() => view('doctor.prescriptions.show'));
    Route::get('/dashboard/prescriptions/{id}/edit', fn() => view('doctor.prescriptions.edit'));
    Route::get('/dashboard/records',           fn() => view('doctor.records.index'));
    Route::get('/dashboard/records/create',    fn() => view('doctor.records.create'));
    Route::get('/dashboard/records/{id}',      fn() => view('doctor.records.show'));
    Route::get('/dashboard/records/{id}/edit', fn() => view('doctor.records.edit'));
});
// Doctor Dashboard Routes
Route::get('/metrics', [DoctorDashboardController::class, 'metrics']);
Route::get('/patients', [DoctorDashboardController::class, 'patients']);
Route::get('/patients/{id}', [DoctorDashboardController::class, 'patientDetails']);
Route::get('/patients/{id}/prescriptions', [DoctorDashboardController::class, 'patientPrescriptions']);
Route::get('/today-activity', [DoctorDashboardController::class, 'todayActivity']);
Route::get('/upcoming-tasks', [DoctorDashboardController::class, 'upcomingTasks']);
Route::get('/prescriptions', [DoctorDashboardController::class, 'prescriptions']);
Route::get('/records', [DoctorDashboardController::class, 'records']);
