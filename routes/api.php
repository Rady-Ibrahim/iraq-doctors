<?php

use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->group(function () {
    require __DIR__.'/../Modules/Auth/Routes/api.php';
    require __DIR__.'/../Modules/Doctor/Routes/api.php';
    require __DIR__.'/../Modules/Appointment/Routes/api.php';
    require __DIR__.'/../Modules/Review/Routes/api.php';
});
