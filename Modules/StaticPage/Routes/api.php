<?php

use Illuminate\Support\Facades\Route;
use Modules\StaticPage\Http\Controllers\Api\StaticPageController;

Route::prefix('pages')->group(function () {
    Route::get('/', [StaticPageController::class, 'index']);
    Route::get('/{slug}', [StaticPageController::class, 'show']);
});

Route::middleware('auth:sanctum')->prefix('admin/pages')->group(function () {
    Route::post('/', [StaticPageController::class, 'store']);
    Route::get('/', [StaticPageController::class, 'index']);
    Route::put('/{id}', [StaticPageController::class, 'update']);
    Route::delete('/{id}', [StaticPageController::class, 'destroy']);
});

