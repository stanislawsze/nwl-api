<?php

namespace App\Domain\Discord\DTOs;

readonly class DiscordGuildRoleDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public int $position,
        public int $color,
        public bool $managed,
        public bool $mentionable,
    ) {}
}
