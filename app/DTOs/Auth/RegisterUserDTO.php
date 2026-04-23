<?php

namespace App\DTOs\Auth;

class RegisterUserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return ['name' => $this->name, 'email' => $this->email, 'password' => $this->password];
    }
}
