<?php

namespace App\Domain\Discord\Services;

use App\Domain\Discord\DTOs\DiscordOAuthUserDTO;
use App\Models\DiscordAccount;
use App\Models\DiscordIntegration;
use App\Models\DiscordRoleMapping;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class DiscordIntegrationService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function upsertIntegration(User $owner, array $attributes): DiscordIntegration
    {
        return DB::transaction(function () use ($owner, $attributes) {
            $payload = [
                'guild_id' => $attributes['guild_id'],
                'guild_name' => $attributes['guild_name'],
                'bot_enabled' => $attributes['bot_enabled'] ?? false,
                'is_active' => $attributes['is_active'] ?? true,
                'oauth_client_id' => $attributes['oauth_client_id'] ?? null,
                'oauth_redirect_uri' => $attributes['oauth_redirect_uri'] ?? null,
                'settings' => $attributes['settings'] ?? [],
            ];

            if (array_key_exists('oauth_client_secret', $attributes)) {
                $payload['oauth_client_secret'] = $attributes['oauth_client_secret'];
            }

            if (array_key_exists('bot_token', $attributes)) {
                $payload['bot_token'] = $attributes['bot_token'];
            }

            $integration = DiscordIntegration::query()->updateOrCreate(
                ['owner_user_id' => $owner->id],
                $payload,
            );

            return $integration->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createRoleMapping(User $owner, array $attributes): DiscordRoleMapping
    {
        $integration = $this->ownedIntegrationOrFail($owner, (int) $attributes['discord_integration_id']);
        $localRole = Role::query()->findOrFail((int) $attributes['local_role_id']);

        return DiscordRoleMapping::query()->create([
            'discord_integration_id' => $integration->id,
            'discord_role_id' => $attributes['discord_role_id'],
            'discord_role_name' => $attributes['discord_role_name'],
            'local_role_id' => $localRole->id,
        ])->load('localRole');
    }

    public function deleteRoleMapping(User $owner, DiscordRoleMapping $mapping): void
    {
        $this->ownedIntegrationOrFail($owner, $mapping->discord_integration_id);

        $mapping->delete();
    }

    public function linkDiscordAccount(User $user, DiscordOAuthUserDTO $discordUser): DiscordAccount
    {
        return DiscordAccount::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'discord_user_id' => $discordUser->discordUserId,
                'username' => $discordUser->username,
                'global_name' => $discordUser->globalName,
                'email' => $discordUser->email,
                'avatar' => $discordUser->avatar,
                'access_token' => $discordUser->accessToken,
                'refresh_token' => $discordUser->refreshToken,
                'token_expires_at' => $discordUser->tokenExpiresAt,
            ],
        );
    }

    /**
     * @param  array<int, string>  $discordRoleIds
     */
    public function syncMappedRoles(User $user, DiscordIntegration $integration, array $discordRoleIds): void
    {
        $mappings = $integration->roleMappings()->with('localRole')->get();

        $localRoleIds = [];

        foreach ($mappings as $mapping) {
            if (in_array($mapping->discord_role_id, $discordRoleIds, true) && $mapping->local_role_id !== null) {
                $localRoleIds[] = $mapping->local_role_id;
            }
        }

        if ($localRoleIds === []) {
            return;
        }

        $roleNames = Role::query()
            ->whereIn('id', $localRoleIds)
            ->pluck('name')
            ->all();

        if ($roleNames !== []) {
            $user->syncRoles($roleNames);
        }
    }

    public function ownedIntegrationOrFail(User $owner, int $integrationId): DiscordIntegration
    {
        $integration = DiscordIntegration::query()
            ->whereKey($integrationId)
            ->where('owner_user_id', $owner->id)
            ->first();

        if ($integration === null) {
            throw ValidationException::withMessages([
                'discord_integration_id' => ['The selected Discord integration is invalid.'],
            ]);
        }

        return $integration;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateCredentials(User $owner, array $attributes): DiscordIntegration
    {
        $integration = $owner->ownedDiscordIntegrations()->first();

        if ($integration === null) {
            throw ValidationException::withMessages([
                'discord_integration' => ['Create a Discord integration before updating credentials.'],
            ]);
        }

        $integration->fill($attributes);
        $integration->save();

        return $integration->refresh();
    }

    /**
     * @param  array<int, string>  $fields
     */
    public function clearCredentials(User $owner, array $fields): DiscordIntegration
    {
        $integration = $owner->ownedDiscordIntegrations()->first();

        if ($integration === null) {
            throw ValidationException::withMessages([
                'discord_integration' => ['Create a Discord integration before clearing credentials.'],
            ]);
        }

        foreach ($fields as $field) {
            $integration->{$field} = null;
        }

        $integration->save();

        return $integration->refresh();
    }
}
