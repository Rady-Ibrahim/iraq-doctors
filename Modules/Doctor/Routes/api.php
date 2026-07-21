<?php

use Illuminate\Support\Facades\Route;
use Modules\Doctor\Http\Controllers\Api\DoctorController;
use Modules\Doctor\Http\Controllers\Api\DoctorBranchController;

Route::prefix('doctors')->group(function () {
    Route::get('/', [DoctorController::class, 'index']);
    Route::get('/featured', [DoctorController::class, 'featured']);
    Route::get('/nearby', [DoctorController::class, 'nearby']);
    Route::get('/specialities', [DoctorController::class, 'specialities']);
    Route::get('/governorates', [DoctorController::class, 'governorates']);
    Route::get('/branches/nearby', [DoctorBranchController::class, 'nearby']);
    Route::get('/branches/{branchId}', [DoctorBranchController::class, 'show']);

    Route::get('/{id}', [DoctorController::class, 'show']);
    Route::get('/{id}/schedule', [DoctorController::class, 'schedule']);
    Route::get('/{id}/branches', [DoctorBranchController::class, 'index']);
});
