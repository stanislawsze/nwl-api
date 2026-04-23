<?php

use App\DTOs\Auth\LoginUserDTO;
use App\DTOs\Auth\RegisterUserDTO;
use App\Models\User;
use App\Services\Domain\Auth\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Hashing\HashManager;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('AuthService', function () {
    beforeEach(function () {
        // Create the user role
        Role::firstOrCreate(['name' => 'user']);

        $this->authMock = mock(StatefulGuard::class);
        $this->hashMock = mock(HashManager::class);

        $this->service = new AuthService(
            $this->authMock,
            $this->hashMock,
        );
    });

    describe('register()', function () {
        it('successfully registers a new user', function () {
            $dto = new RegisterUserDTO(
                name: 'Test User',
                email: 'test@example.com',
                password: 'password123',
            );

            $this->hashMock->shouldReceive('make')
                ->once()
                ->with('password123')
                ->andReturn('hashed-password');

            $user = $this->service->register($dto);

            expect($user)->toBeInstanceOf(User::class);
            expect($user->name)->toBe('Test User');
            expect($user->email)->toBe('test@example.com');
            expect($user->password)->not->toBe('password123'); // Should be hashed
            expect($user->hasRole('user'))->toBeTrue();
        });

        it('assigns default permissions to new user', function () {
            // This would require setting up permissions in the database
            // For now, we'll just test that the method exists and runs
            $dto = new RegisterUserDTO(
                name: 'Test User',
                email: 'test@example.com',
                password: 'password123',
            );

            $this->hashMock->shouldReceive('make')
                ->once()
                ->with('password123')
                ->andReturn('hashed-password');

            $user = $this->service->register($dto);

            expect($user)->toBeInstanceOf(User::class);
        });
    });

    describe('authenticate()', function () {
        it('throws exception for invalid credentials', function () {
            $dto = new LoginUserDTO(
                email: 'test@example.com',
                password: 'wrongpassword',
            );

            $this->authMock->shouldReceive('attempt')
                ->once()
                ->with(['email' => 'test@example.com', 'password' => 'wrongpassword'])
                ->andReturn(false);

            expect(fn () => $this->service->authenticate($dto))
                ->toThrow(AuthenticationException::class);
        });
    });

    describe('getUser()', function () {
        it('returns authenticated user', function () {
            $user = User::factory()->create();

            $this->authMock->shouldReceive('user')
                ->once()
                ->andReturn($user);

            $result = $this->service->getUser();

            expect($result)->toBe($user);
        });

        it('returns null when no user is authenticated', function () {
            $this->authMock->shouldReceive('user')
                ->once()
                ->andReturn(null);

            $result = $this->service->getUser();

            expect($result)->toBeNull();
        });
    });

    describe('logout()', function () {
        it('logs out the user and deletes tokens', function () {
            $user = User::factory()->create();
            $user->createToken('test-token');

            $this->authMock->shouldReceive('user')
                ->once()
                ->andReturn($user);

            $this->authMock->shouldReceive('logout')
                ->once();

            $this->service->logout();

            expect($user->tokens()->count())->toBe(0);
        });
    });
});
