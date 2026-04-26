<?php

use App\Http\Controllers\Api\V1\DiscordIntegrationController;
use Illuminate\Support\Facades\Route;

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
