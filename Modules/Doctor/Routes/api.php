<?php

use Illuminate\Support\Facades\Route;
use Modules\Doctor\Http\Controllers\Api\DoctorController;
use Modules\Doctor\Http\Controllers\Api\DoctorBranchController;

Route::prefix('doctors')->group(function () {
    Route::get('/', [DoctorController::class, 'index']);
    Route::get('/specialities', [DoctorController::class, 'specialities']);
    Route::get('/branches/nearby', [DoctorBranchController::class, 'nearby']);

    Route::get('/{id}', [DoctorController::class, 'show']);
    Route::get('/{id}/schedule', [DoctorController::class, 'schedule']);
});
