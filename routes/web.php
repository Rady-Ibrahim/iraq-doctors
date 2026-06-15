<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Blade views only (no API logic here)
|--------------------------------------------------------------------------
| These routes serve HTML pages. Auth checking is done on the frontend
| via JS — the actual API auth is Sanctum token-based via /admin/auth/me
| and /doctor/dashboard/* routes.
|
| No 'auth' middleware here to avoid "Route [login] not defined" error.
*/

// ── Public ────────────────────────────────────────────────────────────────
Route::get('/', fn() => view('admin.login'));

// ── Admin SPA pages (served publicly — JS handles auth redirect) ──────────
Route::prefix('admin')->group(function () {
    Route::get('/login',                   fn() => view('admin.login'))->name('admin.login');
    Route::get('/dashboard',               fn() => view('admin.dashboard'));
    Route::get('/dashboard/doctors',       fn() => view('admin.doctors.index'));
    Route::get('/dashboard/doctors/{id}',  fn() => view('admin.doctors.show'));
    Route::get('/dashboard/patients',      fn() => view('admin.patients.index'));
    Route::get('/dashboard/patients/{id}', fn() => view('admin.patients.show'));
    Route::get('/dashboard/appointments',      fn() => view('admin.appointments.index'));
    Route::get('/dashboard/appointments/{id}', fn() => view('admin.appointments.show'));
    Route::get('/users',      fn() => view('admin.users.index'));
    Route::get('/users/{id}', fn() => view('admin.users.show'));
    Route::get('/dashboard/revenue',   fn() => view('admin.revenue'));
    Route::get('/dashboard/analytics', fn() => view('admin.analytics'));
});

// ── Doctor SPA pages (served publicly — JS handles auth redirect) ─────────
Route::prefix('doctor')->group(function () {
    Route::get('/login',     fn() => view('doctor.login'))->name('doctor.login');
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
