<?php

use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\TenantInvitationController;
use App\Http\Controllers\Api\V1\TenantMemberController;
use Illuminate\Support\Facades\Route;

Route::prefix('tenants')
    ->name('tenants.')
    ->group(function () {
        Route::controller(TenantController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::post('/{tenant}/switch', 'switchCurrent')->name('switch');
        });

        Route::prefix('current')->group(function () {
            Route::prefix('members')
                ->controller(TenantMemberController::class)
                ->name('members.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/', 'store')->name('store');
                    Route::patch('/{user}', 'update')->name('update');
                    Route::delete('/{user}', 'destroy')->name('destroy');
                });

            Route::prefix('invitations')
                ->controller(TenantInvitationController::class)
                ->name('invitations.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/', 'store')->name('store');
                    Route::post('/{tenantInvitation}/resend', 'resend')->name('resend');
                    Route::delete('/{tenantInvitation}', 'destroy')->name('destroy');
                });
        });

        Route::post('/invitations/{token}/accept', [TenantInvitationController::class, 'accept'])
            ->name('invitations.accept');
    });
