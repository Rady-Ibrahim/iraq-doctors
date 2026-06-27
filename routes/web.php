<?php

use App\Http\Controllers\StorageFileController;
use Illuminate\Support\Facades\Route;

Route::get('/files/{path}', [StorageFileController::class, 'show'])
    ->where('path', '.*')
    ->name('files.show');
