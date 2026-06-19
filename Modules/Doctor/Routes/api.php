<?php

use Illuminate\Support\Facades\Route;
use Modules\Doctor\Http\Controllers\Api\DoctorController;
use Modules\Doctor\Http\Controllers\Api\DoctorBranchController;

Route::prefix('doctors')->group(function () {
    // Public routes
    Route::get('/fff', [DoctorController::class, 'index']);
    Route::get('/specialities', [DoctorController::class, 'specialities']);

    Route::middleware('auth:sanctum')->group(function () {
        // Branch routes — static routes BEFORE parameterized ones
        Route::get('/branches/nearby', [DoctorBranchController::class, 'nearby']);
        Route::get('/branches/{branchId}', [DoctorBranchController::class, 'show']);
        Route::put('/branches/{branchId}', [DoctorBranchController::class, 'update']);
        Route::delete('/branches/{branchId}', [DoctorBranchController::class, 'destroy']);
    });

    // Public doctor routes — after /branches to avoid conflict
    Route::get('/{id}', [DoctorController::class, 'show']);
    Route::get('/{id}/schedule', [DoctorController::class, 'schedule']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/{doctorId}/branches', [DoctorBranchController::class, 'index']);
        Route::post('/{doctorId}/branches', [DoctorBranchController::class, 'store']);
    });
});
