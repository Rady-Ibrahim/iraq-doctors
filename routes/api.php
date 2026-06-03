<?php

use Illuminate\Support\Facades\Route;

// Laravel already prefixes this file with /api via RouteServiceProvider
Route::prefix('v1')->group(function () {
    require __DIR__.'/../Modules/Auth/Routes/api.php';
    require __DIR__.'/../Modules/Doctor/Routes/api.php';
    require __DIR__.'/../Modules/Appointment/Routes/api.php';
    require __DIR__.'/../Modules/Review/Routes/api.php';
    require __DIR__.'/../Modules/MedicalRecord/Routes/api.php';
    require __DIR__.'/../Modules/StaticPage/Routes/api.php';
});
