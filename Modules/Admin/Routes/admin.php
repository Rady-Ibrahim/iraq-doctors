<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\Api\AdminDashboardController;

// Admin Dashboard Routes
Route::prefix('admin')->group(function () {
    // صفحة اللوجين
    Route::get('/login',  fn() => view('admin.login'))->name('admin.login');
    
    // صفحات لوحة التحكم المربوطة بالكنترولر لشحن البيانات
    Route::get('/dashboard',             [AdminDashboardController::class, 'metrics'])->name('admin.dashboard');
    Route::get('/dashboard/doctors',       [AdminDashboardController::class, 'doctorsStats'])->name('admin.doctors.index');
    Route::get('/dashboard/patients',      [AdminDashboardController::class, 'patientsStats'])->name('admin.patients.index');
    Route::get('/dashboard/appointments',  [AdminDashboardController::class, 'appointmentsStats'])->name('admin.appointments.index');
    Route::get('/dashboard/revenue',       [AdminDashboardController::class, 'revenueStats'])->name('admin.revenue');
    Route::get('/dashboard/analytics',     [AdminDashboardController::class, 'analytics'])->name('admin.analytics');

    // صفحات الـ Views الشاغرة حالياً (لحين ربطها بالبيانات لاحقاً)
    Route::get('/dashboard/doctors/{id}',  fn() => view('admin.doctors.show'))->name('admin.doctors.show');
    Route::get('/dashboard/patients/{id}', fn() => view('admin.patients.show'))->name('admin.patients.show');
    Route::get('/dashboard/appointments/{id}', fn() => view('admin.appointments.show'))->name('admin.appointments.show');
    Route::get('/users',                   fn() => view('admin.users.index'))->name('admin.users.index');
    Route::get('/users/{id}',              fn() => view('admin.users.show'))->name('admin.users.show');

    // الإجراءات (Actions)
    Route::post('/doctors/{id}/approve',   [AdminDashboardController::class, 'approveDoctor'])->name('admin.doctors.approve');
    Route::post('/doctors/{id}/reject',    [AdminDashboardController::class, 'rejectDoctor'])->name('admin.doctors.reject');
    Route::post('/doctors/{id}/suspend',   [AdminDashboardController::class, 'suspendDoctor'])->name('admin.doctors.suspend');
    Route::post('/doctors/{id}/activate',  [AdminDashboardController::class, 'activateDoctor'])->name('admin.doctors.activate');
});