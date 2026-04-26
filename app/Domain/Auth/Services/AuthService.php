<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\DTOs\AuthenticatedUserDTO;
use App\Domain\Auth\DTOs\LoginUserDTO;
use App\Domain\Auth\DTOs\RegisterUserDTO;
use App\Domain\Auth\Events\UserAuthenticated;
use App\Domain\Auth\Events\UserRegistered;
use App\Domain\Tenancy\Services\TenancyAuditLogService;
use App\Domain\Tenancy\Services\TenancyService;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Hashing\HashManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuthService
{
    public function __construct(
        protected AuthFactory $auth,
        protected HashManager $hash,
        protected TenancyService $tenancyService,
        protected TenancyAuditLogService $tenancyAuditLog,
    ) {}

    public function register(RegisterUserDTO $dto): AuthenticatedUserDTO
    {
        return DB::transaction(function () use ($dto) {
            $user = $this->createUser($dto);

            return $this->buildAuthenticatedUserDTO($user);
        });
    }

    public function registerFromInvitation(RegisterUserDTO $dto, string $token): AuthenticatedUserDTO
    {
        return DB::transaction(function () use ($dto, $token) {
            if (User::query()->where('email', $dto->email)->exists()) {
                throw ValidationException::withMessages([
                    'email' => ['An account already exists for this invitation email. Please sign in and accept the invitation.'],
                ]);
            }

            $user = $this->createUser($dto);
            $invitation = $this->tenancyService->acceptInvitation($user, $token);
            $this->tenancyAuditLog->invitationRegistered($user, $invitation);

            $user->loadMissing('currentTenant');

            return $this->buildAuthenticatedUserDTO($user);
        });
    }

    public function authenticate(LoginUserDTO $dto): AuthenticatedUserDTO
    {
        $guard = $this->statefulGuard();

        if (! $guard->attempt($dto->toArray())) {
            throw new AuthenticationException('The provided credentials are incorrect.');
        }

        $user = $guard->user();
        $this->tenancyService->ensurePersonalTenant($user);
        event(new UserAuthenticated($user, 'password'));

        return $this->buildAuthenticatedUserDTO($user);
    }

    public function getUser(): ?User
    {
        return $this->auth->guard()->user();
    }

    public function logout(): void
    {
        $guard = $this->auth->guard();

        $guard->user()?->tokens()->delete();

        if ($guard instanceof StatefulGuard) {
            $guard->logout();
        }
    }

    public function refreshToken(User $user, ?PersonalAccessToken $currentToken): string
    {
        if ($currentToken === null) {
            throw new AuthenticationException('No active token found.');
        }

        $currentToken->delete();

        return $user->createToken('auth-token')->plainTextToken;
    }

    public function issueTokenFor(User $user): AuthenticatedUserDTO
    {
        return $this->buildAuthenticatedUserDTO($user);
    }

    protected function assignDefaultPermissions(User $user): void
    {
        $permissions = Permission::whereIn('name', [
            'view servers',
            'view players',
        ])->get();

        $user->permissions()->syncWithoutDetaching($permissions);
    }

    protected function buildAuthenticatedUserDTO(User $user): AuthenticatedUserDTO
    {
        $user->loadMissing('roles', 'permissions');

        return new AuthenticatedUserDTO(
            userId: $user->id,
            token: $user->createToken('auth-token')->plainTextToken,
            roles: $user->roles->pluck('name')->toArray(),
            permissions: $user->permissions->pluck('name')->toArray(),
        );
    }

    protected function statefulGuard(): StatefulGuard
    {
        $guard = $this->auth->guard(config('auth.defaults.guard'));

        if (! $guard instanceof StatefulGuard) {
            throw new AuthenticationException('The configured authentication guard must be stateful.');
        }

        return $guard;
    }

    protected function createUser(RegisterUserDTO $dto): User
    {
        $user = User::query()->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $this->hash->make($dto->password),
        ]);

        $role = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $user->assignRole($role);

        $this->assignDefaultPermissions($user);
        $this->tenancyService->ensurePersonalTenant($user);

        event(new UserRegistered($user));
        event(new UserAuthenticated($user, 'password'));

        return $user;
    }
}
