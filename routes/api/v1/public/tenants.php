<?php

use App\Http\Controllers\Api\V1\TenantInvitationController;
use Illuminate\Support\Facades\Route;

Route::prefix('tenants')
    ->name('tenants.')
    ->controller(TenantInvitationController::class)
    ->group(function () {
        Route::get('/invitations/{token}', 'show')->name('invitations.show');
        Route::post('/invitations/{token}/register', 'register')->name('invitations.register');
    });
