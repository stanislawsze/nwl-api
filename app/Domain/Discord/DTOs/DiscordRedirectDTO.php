<?php

namespace App\Domain\Discord\DTOs;

readonly class DiscordRedirectDTO
{
    public function __construct(
        public string $authorizationUrl,
        public string $state,
    ) {}
}
