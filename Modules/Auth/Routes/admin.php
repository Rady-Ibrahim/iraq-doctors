<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Admin\AdminUserController;

// Admin User Management Routes
Route::get('/', [AdminUserController::class, 'index']);
Route::get('/{id}', [AdminUserController::class, 'show']);
Route::post('/{id}/block', [AdminUserController::class, 'block']);
Route::post('/{id}/unblock', [AdminUserController::class, 'unblock']);
Route::delete('/{id}', [AdminUserController::class, 'destroy']);

// Create admin and doctor accounts (Dashboard only)
Route::post('/admin/create', [AdminUserController::class, 'createAdmin']);
Route::post('/doctor/create', [AdminUserController::class, 'createDoctor']);
