<?php

namespace App\Domain\Discord\DTOs;

use Carbon\CarbonImmutable;

readonly class DiscordOAuthUserDTO
{
    /**
     * @param  array<int, string>  $guildRoleIds
     */
    public function __construct(
        public string $discordUserId,
        public string $username,
        public ?string $globalName,
        public ?string $email,
        public ?string $avatar,
        public string $accessToken,
        public ?string $refreshToken,
        public ?CarbonImmutable $tokenExpiresAt,
        public array $guildRoleIds = [],
    ) {}
}
