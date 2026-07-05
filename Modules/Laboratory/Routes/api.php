<?php

use Illuminate\Support\Facades\Route;
use Modules\Laboratory\Http\Controllers\Api\LaboratoryController;
use Modules\Laboratory\Http\Controllers\Api\LaboratoryOrderController;

Route::prefix('laboratories')->group(function () {
    Route::get('/', [LaboratoryController::class, 'index']);
    Route::get('/nearby', [LaboratoryController::class, 'nearby']);
    Route::get('/{id}', [LaboratoryController::class, 'show']);
    Route::get('/{id}/tests', [LaboratoryController::class, 'tests']);
});

Route::middleware('auth:sanctum')->prefix('laboratory-orders')->group(function () {
    Route::post('/', [LaboratoryOrderController::class, 'store']);
    Route::get('/my', [LaboratoryOrderController::class, 'myOrders']);
    Route::get('/{id}', [LaboratoryOrderController::class, 'show']);
    Route::post('/{id}/cancel', [LaboratoryOrderController::class, 'cancel']);
    Route::post('/{id}/accept-quote', [LaboratoryOrderController::class, 'acceptQuote']);
});
