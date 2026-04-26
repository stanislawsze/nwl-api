<?php

use App\Domain\Auth\DTOs\AuthenticatedUserDTO;
use App\Domain\Auth\DTOs\LoginUserDTO;
use App\Domain\Auth\DTOs\RegisterUserDTO;
use App\Domain\Auth\Services\AuthService;
use App\Domain\Tenancy\Services\TenancyAuditLogService;
use App\Domain\Tenancy\Services\TenancyService;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Hashing\HashManager;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user']);
});

it('registers a new user', function (): void {
    $authenticatedUser = makeAuthService()->register(new RegisterUserDTO(
        name: 'Test User',
        email: 'test@example.com',
        password: 'password123',
    ));

    expect($authenticatedUser)->toBeInstanceOf(AuthenticatedUserDTO::class);

    $user = User::query()->findOrFail($authenticatedUser->userId);

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->password)->not->toBe('password123');
    expect($user->hasRole('user'))->toBeTrue();
    expect($authenticatedUser->token)->not->toBe('');
});

it('assigns default permissions when registering a user', function (): void {
    $authenticatedUser = makeAuthService()->register(new RegisterUserDTO(
        name: 'Test User',
        email: 'test@example.com',
        password: 'password123',
    ));

    expect($authenticatedUser)->toBeInstanceOf(AuthenticatedUserDTO::class);
});

it('throws an exception for invalid credentials', function (): void {
    expect(fn () => makeAuthService()->authenticate(new LoginUserDTO(
        email: 'test@example.com',
        password: 'wrongpassword',
    )))->toThrow(AuthenticationException::class);
});

it('returns the authenticated user', function (): void {
    $user = User::factory()->create();
    auth()->login($user);

    expect(makeAuthService()->getUser())->toBe($user);
});

it('returns null when no user is authenticated', function (): void {
    auth()->logout();

    expect(makeAuthService()->getUser())->toBeNull();
});

it('logs out the user and deletes tokens', function (): void {
    $user = User::factory()->create();
    $user->createToken('test-token');
    auth()->login($user);

    makeAuthService()->logout();

    expect($user->tokens()->count())->toBe(0);
});

function makeAuthService(): AuthService
{
    return new AuthService(
        app(AuthFactory::class),
        app(HashManager::class),
        app(TenancyService::class),
        app(TenancyAuditLogService::class),
    );
}
