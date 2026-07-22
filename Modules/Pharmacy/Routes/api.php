<?php

use Illuminate\Support\Facades\Route;
use Modules\Pharmacy\Http\Controllers\Api\PharmacyController;
use Modules\Pharmacy\Http\Controllers\Api\PharmacyBranchController;
use Modules\Pharmacy\Http\Controllers\Api\PharmacyOrderController;

Route::prefix('pharmacies')->group(function () {
    Route::get('/', [PharmacyController::class, 'index']);
    Route::get('/nearby', [PharmacyController::class, 'nearby']);
    Route::get('/medicine-prices', [PharmacyController::class, 'compareMedicinePrices']);
    Route::get('/branches/nearby', [PharmacyBranchController::class, 'nearby']);
    Route::get('/branches/{branchId}', [PharmacyBranchController::class, 'show']);
    Route::get('/{id}', [PharmacyController::class, 'show']);
    Route::get('/{id}/medicines', [PharmacyController::class, 'medicines']);
    Route::get('/{id}/branches', [PharmacyBranchController::class, 'index']);
});

Route::middleware('auth:sanctum')->prefix('pharmacy-orders')->group(function () {
    Route::post('/', [PharmacyOrderController::class, 'store']);
    Route::get('/my', [PharmacyOrderController::class, 'myOrders']);
    Route::get('/{id}', [PharmacyOrderController::class, 'show']);
    Route::post('/{id}/cancel', [PharmacyOrderController::class, 'cancel']);
    Route::post('/{id}/accept-quote', [PharmacyOrderController::class, 'acceptQuote']);
});
