<?php

namespace App\Domain\Discord\Services;

use App\Domain\Discord\DTOs\DiscordOAuthUserDTO;
use App\Domain\Discord\DTOs\DiscordRedirectDTO;
use App\Models\DiscordIntegration;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

class DiscordOAuthService
{
    public function __construct(
        protected HttpFactory $http,
    ) {}

    public function buildAuthorizationRedirect(?DiscordIntegration $integration = null): DiscordRedirectDTO
    {
        $state = Str::random(40);

        Cache::put($this->stateCacheKey($state), [
            'integration_id' => $integration?->id,
        ], now()->addMinutes(10));

        $query = http_build_query([
            'client_id' => $this->oauthClientId($integration),
            'redirect_uri' => $this->oauthRedirectUri($integration),
            'response_type' => 'code',
            'scope' => implode(' ', config('discord.oauth_scopes')),
            'state' => $state,
            'prompt' => 'consent',
        ]);

        return new DiscordRedirectDTO(
            authorizationUrl: rtrim(config('discord.authorization_url'), '?') . '?' . $query,
            state: $state,
        );
    }

    /**
     * @return array{integration_id: int|null, user: DiscordOAuthUserDTO}
     */
    public function fetchUserFromAuthorizationCode(string $code, string $state): array
    {
        $context = Cache::pull($this->stateCacheKey($state));

        if (! is_array($context)) {
            throw new RuntimeException('The Discord OAuth state is invalid or has expired.');
        }

        $guildRoleIds = [];
        $integrationId = $context['integration_id'] ?? null;
        $integration = null;

        if (is_int($integrationId)) {
            $integration = DiscordIntegration::query()->find($integrationId);
        }

        $tokenResponse = $this->http->asForm()->post(config('discord.token_url'), [
            'client_id' => $this->oauthClientId($integration),
            'client_secret' => $this->oauthClientSecret($integration),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->oauthRedirectUri($integration),
        ])->throw()->json();

        $accessToken = (string) ($tokenResponse['access_token'] ?? '');

        if ($accessToken === '') {
            throw new RuntimeException('Discord did not return an access token.');
        }

        $userResponse = $this->http->withToken($accessToken)
            ->get(config('discord.api_base_url') . '/users/@me')
            ->throw()
            ->json();

        if ($integration !== null) {
            $memberResponse = $this->http->withToken($accessToken)
                ->get(config('discord.api_base_url') . "/users/@me/guilds/{$integration->guild_id}/member")
                ->throw()
                ->json();

            $guildRoleIds = array_values(array_map('strval', $memberResponse['roles'] ?? []));
        }

        return [
            'integration_id' => is_int($integrationId) ? $integrationId : null,
            'user' => new DiscordOAuthUserDTO(
                discordUserId: (string) $userResponse['id'],
                username: (string) $userResponse['username'],
                globalName: isset($userResponse['global_name']) ? (string) $userResponse['global_name'] : null,
                email: isset($userResponse['email']) ? (string) $userResponse['email'] : null,
                avatar: isset($userResponse['avatar']) ? $this->avatarUrl((string) $userResponse['id'], (string) $userResponse['avatar']) : null,
                accessToken: $accessToken,
                refreshToken: isset($tokenResponse['refresh_token']) ? (string) $tokenResponse['refresh_token'] : null,
                tokenExpiresAt: isset($tokenResponse['expires_in']) ? CarbonImmutable::now()->addSeconds((int) $tokenResponse['expires_in']) : null,
                guildRoleIds: $guildRoleIds,
            ),
        ];
    }

    protected function stateCacheKey(string $state): string
    {
        return 'discord-oauth-state:' . $state;
    }

    protected function avatarUrl(string $userId, string $avatarHash): string
    {
        return sprintf('%s/users/%s/avatars/%s.png', rtrim(config('discord.cdn_base_url'), '/'), $userId, $avatarHash);
    }

    protected function oauthClientId(?DiscordIntegration $integration): string
    {
        return $this->requiredConfigValue(
            $integration?->oauth_client_id,
            (string) config('services.discord.client_id'),
            'Discord OAuth client ID',
        );
    }

    protected function oauthClientSecret(?DiscordIntegration $integration): string
    {
        return $this->requiredConfigValue(
            $integration?->oauth_client_secret,
            (string) config('services.discord.client_secret'),
            'Discord OAuth client secret',
        );
    }

    protected function oauthRedirectUri(?DiscordIntegration $integration): string
    {
        return $this->requiredConfigValue(
            $integration?->oauth_redirect_uri,
            (string) config('services.discord.redirect'),
            'Discord OAuth redirect URI',
        );
    }

    protected function requiredConfigValue(?string $integrationValue, string $defaultValue, string $label): string
    {
        $value = $integrationValue ?: $defaultValue;

        if ($value === '') {
            throw new RuntimeException($label . ' is not configured.');
        }

        return $value;
    }
}
