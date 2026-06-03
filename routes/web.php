<?php

use Illuminate\Support\Facades\Route;

// Public Home
Route::get('/', function () {
    return view('welcome');
});

// Admin Dashboard Routes
Route::prefix('admin')->group(function () {
    Route::get('/login', function () {
        return view('admin.login');
    })->name('admin.login');
    
    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('admin.dashboard');
        
        // Doctors Management
        Route::get('/dashboard/doctors', function () {
            return view('admin.doctors.index');
        })->name('admin.doctors.index');
        Route::get('/dashboard/doctors/{id}', function () {
            return view('admin.doctors.show');
        })->name('admin.doctors.show');
        
        // Patients Management
        Route::get('/dashboard/patients', function () {
            return view('admin.patients.index');
        })->name('admin.patients.index');
        Route::get('/dashboard/patients/{id}', function () {
            return view('admin.patients.show');
        })->name('admin.patients.show');
        
        // Appointments Management
        Route::get('/dashboard/appointments', function () {
            return view('admin.appointments.index');
        })->name('admin.appointments.index');
        Route::get('/dashboard/appointments/{id}', function () {
            return view('admin.appointments.show');
        })->name('admin.appointments.show');
        
        // Users Management
        Route::get('/users', function () {
            return view('admin.users.index');
        })->name('admin.users.index');
        Route::get('/users/{id}', function () {
            return view('admin.users.show');
        })->name('admin.users.show');

        // Revenue & Analytics
        Route::get('/dashboard/revenue', function () {
            return view('admin.revenue');
        })->name('admin.revenue');
        Route::get('/dashboard/analytics', function () {
            return view('admin.analytics');
        })->name('admin.analytics');
    });
});

// Doctor Dashboard Routes
Route::prefix('doctor')->group(function () {
    Route::get('/login', function () {
        return view('doctor.login');
    })->name('doctor.login');
    
    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', function () {
            return view('doctor.dashboard');
        })->name('doctor.dashboard');
        
        // Calendar
        Route::get('/dashboard/calendar', function () {
            return view('doctor.calendar');
        })->name('doctor.calendar');
        
        // Settings
        Route::get('/dashboard/settings', function () {
            return view('doctor.settings');
        })->name('doctor.settings');
        
        // Patients Management
        Route::get('/dashboard/patients', function () {
            return view('doctor.patients.index');
        })->name('doctor.patients.index');
        Route::get('/dashboard/patients/{id}', function () {
            return view('doctor.patients.show');
        })->name('doctor.patients.show');
        Route::get('/dashboard/patients/{id}/records', function () {
            return view('doctor.patients.records');
        })->name('doctor.patients.records');
        
        // Prescriptions Management
        Route::get('/dashboard/prescriptions', function () {
            return view('doctor.prescriptions.index');
        })->name('doctor.prescriptions.index');
        Route::get('/dashboard/prescriptions/create', function () {
            return view('doctor.prescriptions.create');
        })->name('doctor.prescriptions.create');
        Route::get('/dashboard/prescriptions/{id}', function () {
            return view('doctor.prescriptions.show');
        })->name('doctor.prescriptions.show');
        Route::get('/dashboard/prescriptions/{id}/edit', function () {
            return view('doctor.prescriptions.edit');
        })->name('doctor.prescriptions.edit');
        
        // Records Management
        Route::get('/dashboard/records', function () {
            return view('doctor.records.index');
        })->name('doctor.records.index');
        Route::get('/dashboard/records/create', function () {
            return view('doctor.records.create');
        })->name('doctor.records.create');
        Route::get('/dashboard/records/{id}', function () {
            return view('doctor.records.show');
        })->name('doctor.records.show');
        Route::get('/dashboard/records/{id}/edit', function () {
            return view('doctor.records.edit');
        })->name('doctor.records.edit');
    });
});
