<?php

namespace App\Domain\Discord\Services;

use App\Domain\Discord\DTOs\DiscordGuildRoleDTO;
use App\Models\DiscordIntegration;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Validation\ValidationException;

class DiscordBotService
{
    public function __construct(
        protected HttpFactory $http,
    ) {}

    /**
     * @return array<int, DiscordGuildRoleDTO>
     */
    public function fetchGuildRoles(DiscordIntegration $integration): array
    {
        $botToken = $integration->bot_token ?: config('discord.bot_token');

        if (! is_string($botToken) || $botToken === '') {
            throw ValidationException::withMessages([
                'bot_token' => ['A Discord bot token must be configured before guild roles can be fetched.'],
            ]);
        }

        $response = $this->http
            ->withToken($botToken, 'Bot')
            ->get(config('discord.api_base_url') . "/guilds/{$integration->guild_id}/roles")
            ->throw()
            ->json();

        $roles = [];

        foreach ($response as $role) {
            if (! is_array($role)) {
                continue;
            }

            $roles[] = new DiscordGuildRoleDTO(
                id: (string) ($role['id'] ?? ''),
                name: (string) ($role['name'] ?? ''),
                position: (int) ($role['position'] ?? 0),
                color: (int) ($role['color'] ?? 0),
                managed: (bool) ($role['managed'] ?? false),
                mentionable: (bool) ($role['mentionable'] ?? false),
            );
        }

        usort($roles, static fn (DiscordGuildRoleDTO $left, DiscordGuildRoleDTO $right): int => $right->position <=> $left->position);

        return $roles;
    }
}
