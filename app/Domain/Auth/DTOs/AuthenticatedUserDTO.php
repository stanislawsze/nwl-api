<?php

namespace App\Domain\Auth\DTOs;

readonly class AuthenticatedUserDTO
{
    /**
     * @param  array<int, string>  $roles
     * @param  array<int, string>  $permissions
     */
    public function __construct(
        public int $userId,
        public string $token,
        public array $roles = [],
        public array $permissions = [],
    ) {}
}
