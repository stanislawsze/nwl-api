<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DiscordAuthController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)
    ->name('auth.')
    ->group(function () {
        Route::post('/register', 'register')->name('register');
        Route::post('/login', 'login')->name('login');
    });

Route::prefix('auth/discord')
    ->controller(DiscordAuthController::class)
    ->name('auth.discord.')
    ->group(function () {
        Route::get('/redirect', 'redirect')->name('redirect');
        Route::get('/callback', 'callback')->name('callback');
    });
