<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DiscordAuthController;
use App\Http\Controllers\Api\V1\DiscordIntegrationController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\TenantInvitationController;
use App\Http\Controllers\Api\V1\TenantMemberController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->as('api.v1.')
    ->group(function () {
        Route::middleware('throttle:60,1')->group(function () {
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
        });

        Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
            Route::controller(AuthController::class)
                ->name('auth.')
                ->group(function () {
                    Route::post('/logout', 'logout')->name('logout');
                    Route::get('/me', 'me')->name('me');
                    Route::post('/refresh', 'refresh')->name('refresh');
                });

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

            Route::prefix('discord')
                ->name('discord.')
                ->controller(DiscordIntegrationController::class)
                ->group(function () {
                    Route::prefix('integration')
                        ->name('integration.')
                        ->group(function () {
                            Route::get('/', 'show')->name('show');
                            Route::put('/', 'upsert')->name('upsert');
                            Route::patch('/credentials', 'updateCredentials')->name('credentials.update');
                            Route::delete('/credentials', 'clearCredentials')->name('credentials.clear');
                        });

                    Route::get('/guild-roles', 'listGuildRoles')->name('guild-roles.index');

                    Route::prefix('role-mappings')
                        ->name('role-mappings.')
                        ->group(function () {
                            Route::post('/', 'storeRoleMapping')->name('store');
                            Route::delete('/{mapping}', 'destroyRoleMapping')->name('destroy');
                        });
                });
        });
    });
