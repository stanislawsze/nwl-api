<?php

namespace App\DTOs\Auth;

class LoginUserDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return ['email' => $this->email, 'password' => $this->password];
    }
}
