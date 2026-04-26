<?php

namespace App\Domain\Discord\Services;

use App\Domain\Auth\DTOs\AuthenticatedUserDTO;
use App\Domain\Auth\Events\UserAuthenticated;
use App\Domain\Auth\Services\AuthService;
use App\Domain\Discord\DTOs\DiscordOAuthUserDTO;
use App\Domain\Discord\Events\DiscordUserLinked;
use App\Domain\Tenancy\Services\TenancyService;
use App\Models\DiscordIntegration;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DiscordAuthenticationService
{
    public function __construct(
        protected AuthService $authService,
        protected DiscordIntegrationService $discordIntegrationService,
        protected TenancyService $tenancyService,
    ) {}

    public function authenticate(DiscordOAuthUserDTO $discordUser, ?DiscordIntegration $integration = null): AuthenticatedUserDTO
    {
        return DB::transaction(function () use ($discordUser, $integration) {
            $user = User::query()
                ->whereHas('discordAccount', fn ($query) => $query->where('discord_user_id', $discordUser->discordUserId))
                ->orWhere('email', $discordUser->email)
                ->first();

            if ($user === null) {
                $user = User::query()->create([
                    'name' => $discordUser->globalName ?? $discordUser->username,
                    'email' => $discordUser->email ?? sprintf('%s@discord.local', $discordUser->discordUserId),
                    'password' => bin2hex(random_bytes(32)),
                ]);
            }

            $this->tenancyService->ensurePersonalTenant($user);

            $this->discordIntegrationService->linkDiscordAccount($user, $discordUser);

            if ($integration !== null) {
                $this->discordIntegrationService->syncMappedRoles($user, $integration, $discordUser->guildRoleIds);
            }

            event(new DiscordUserLinked($user, $integration?->id));
            event(new UserAuthenticated($user, 'discord'));

            return $this->authService->issueTokenFor($user);
        });
    }
}
