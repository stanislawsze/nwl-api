<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)
    ->name('auth.')
    ->group(function () {
        Route::post('/logout', 'logout')->name('logout');
        Route::get('/me', 'me')->name('me');
        Route::post('/refresh', 'refresh')->name('refresh');
    });
