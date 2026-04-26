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
            Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
            Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
            Route::get('/auth/discord/redirect', [DiscordAuthController::class, 'redirect'])->name('auth.discord.redirect');
            Route::get('/auth/discord/callback', [DiscordAuthController::class, 'callback'])->name('auth.discord.callback');
        });

        Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
            Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
            Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
            Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
            Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
            Route::post('/tenants/{tenant}/switch', [TenantController::class, 'switchCurrent'])->name('tenants.switch');
            Route::get('/tenants/current/members', [TenantMemberController::class, 'index'])->name('tenants.members.index');
            Route::post('/tenants/current/members', [TenantMemberController::class, 'store'])->name('tenants.members.store');
            Route::patch('/tenants/current/members/{user}', [TenantMemberController::class, 'update'])->name('tenants.members.update');
            Route::delete('/tenants/current/members/{user}', [TenantMemberController::class, 'destroy'])->name('tenants.members.destroy');
            Route::get('/tenants/current/invitations', [TenantInvitationController::class, 'index'])->name('tenants.invitations.index');
            Route::post('/tenants/current/invitations', [TenantInvitationController::class, 'store'])->name('tenants.invitations.store');
            Route::post('/tenants/current/invitations/{tenantInvitation}/resend', [TenantInvitationController::class, 'resend'])->name('tenants.invitations.resend');
            Route::post('/tenants/invitations/{token}/accept', [TenantInvitationController::class, 'accept'])->name('tenants.invitations.accept');
            Route::delete('/tenants/current/invitations/{tenantInvitation}', [TenantInvitationController::class, 'destroy'])->name('tenants.invitations.destroy');
            Route::get('/discord/integration', [DiscordIntegrationController::class, 'show'])->name('discord.integration.show');
            Route::put('/discord/integration', [DiscordIntegrationController::class, 'upsert'])->name('discord.integration.upsert');
            Route::patch('/discord/integration/credentials', [DiscordIntegrationController::class, 'updateCredentials'])->name('discord.integration.credentials.update');
            Route::delete('/discord/integration/credentials', [DiscordIntegrationController::class, 'clearCredentials'])->name('discord.integration.credentials.clear');
            Route::get('/discord/guild-roles', [DiscordIntegrationController::class, 'listGuildRoles'])->name('discord.guild-roles.index');
            Route::post('/discord/role-mappings', [DiscordIntegrationController::class, 'storeRoleMapping'])->name('discord.role-mappings.store');
            Route::delete('/discord/role-mappings/{mapping}', [DiscordIntegrationController::class, 'destroyRoleMapping'])->name('discord.role-mappings.destroy');
        });
    });
