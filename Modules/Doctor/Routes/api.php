<?php

use Illuminate\Support\Facades\Route;
use Modules\Doctor\Http\Controllers\Api\DoctorController;
use Modules\Doctor\Http\Controllers\Api\DoctorBranchController;

Route::prefix('doctors')->group(function () {
    Route::get('/', [DoctorController::class, 'index']);
    Route::get('/specialities', [DoctorController::class, 'specialities']);
    Route::get('/{id}', [DoctorController::class, 'show']);
    Route::get('/{id}/schedule', [DoctorController::class, 'schedule']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/{doctorId}/branches', [DoctorBranchController::class, 'index']);
        Route::post('/{doctorId}/branches', [DoctorBranchController::class, 'store']);
        Route::get('/branches/{branchId}', [DoctorBranchController::class, 'show']);
        Route::put('/branches/{branchId}', [DoctorBranchController::class, 'update']);
        Route::delete('/branches/{branchId}', [DoctorBranchController::class, 'destroy']);
        Route::get('/branches/nearby', [DoctorBranchController::class, 'nearby']);
    });
});
