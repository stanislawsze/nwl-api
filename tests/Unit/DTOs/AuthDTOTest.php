<?php

use App\Domain\Auth\DTOs\AuthenticatedUserDTO;
use App\Domain\Auth\DTOs\LoginUserDTO;
use App\Domain\Auth\DTOs\RegisterUserDTO;

describe('Authentication DTOs', function () {

    describe('LoginUserDTO', function () {
        it('creates DTO with correct properties', function () {
            $dto = new LoginUserDTO(
                email: 'test@example.com',
                password: 'password123',
            );

            expect($dto->email)->toBe('test@example.com');
            expect($dto->password)->toBe('password123');
        });

        it('returns correct array representation', function () {
            $dto = new LoginUserDTO(
                email: 'test@example.com',
                password: 'password123',
            );

            $expected = [
                'email' => 'test@example.com',
                'password' => 'password123',
            ];

            expect($dto->toArray())->toBe($expected);
        });
    });

    describe('RegisterUserDTO', function () {
        it('creates DTO with correct properties', function () {
            $dto = new RegisterUserDTO(
                name: 'Test User',
                email: 'test@example.com',
                password: 'password123',
            );

            expect($dto->name)->toBe('Test User');
            expect($dto->email)->toBe('test@example.com');
            expect($dto->password)->toBe('password123');
        });

        it('returns correct array representation', function () {
            $dto = new RegisterUserDTO(
                name: 'Test User',
                email: 'test@example.com',
                password: 'password123',
            );

            $expected = [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
            ];

            expect($dto->toArray())->toBe($expected);
        });
    });

    describe('AuthenticatedUserDTO', function () {
        it('creates DTO with correct properties', function () {
            $dto = new AuthenticatedUserDTO(
                userId: 1,
                token: 'test-token',
                roles: ['user', 'admin'],
                permissions: ['view users', 'edit users'],
            );

            expect($dto->userId)->toBe(1);
            expect($dto->token)->toBe('test-token');
            expect($dto->roles)->toBe(['user', 'admin']);
            expect($dto->permissions)->toBe(['view users', 'edit users']);
        });
    });
});
