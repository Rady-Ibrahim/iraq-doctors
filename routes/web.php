<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\Api\AdminDashboardController;

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
