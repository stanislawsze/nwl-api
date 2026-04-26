<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->as('api.v1.')
    ->group(function () {
        Route::middleware('throttle:60,1')->group(function () {
            require __DIR__ . '/api/v1/public/auth.php';
            require __DIR__ . '/api/v1/public/tenants.php';
        });

        Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
            require __DIR__ . '/api/v1/protected/auth.php';
            require __DIR__ . '/api/v1/protected/tenants.php';
            require __DIR__ . '/api/v1/protected/discord.php';
        });
    });
