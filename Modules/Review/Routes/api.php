<?php

use Illuminate\Support\Facades\Route;
use Modules\Review\Http\Controllers\Api\ReviewController;

Route::prefix('reviews')->group(function () {
    Route::get('/doctor/{doctorId}', [ReviewController::class, 'doctorReviews']);
    Route::get('/pharmacy/{pharmacyId}', [ReviewController::class, 'pharmacyReviews']);
    Route::get('/laboratory/{laboratoryId}', [ReviewController::class, 'laboratoryReviews']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [ReviewController::class, 'create']);
        Route::get('/my', [ReviewController::class, 'myReviews']);
    });
});
