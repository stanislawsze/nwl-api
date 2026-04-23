<?php

namespace App\DTOs\Auth;

class AuthenticatedUserDTO
{
    /**
     * @param  array<string>  $roles
     * @param  array<string>  $permissions
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $token,
        public readonly array $roles,
        public readonly array $permissions,
    ) {}
}
