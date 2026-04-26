<?php

namespace App\Domain\Auth\DTOs;

readonly class LoginUserDTO
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    /**
     * @return array{email: string, password: string}
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
}
