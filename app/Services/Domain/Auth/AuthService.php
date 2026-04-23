<?php

namespace App\Services\Domain\Auth;

use App\DTOs\Auth\AuthenticatedUserDTO;
use App\DTOs\Auth\LoginUserDTO;
use App\DTOs\Auth\RegisterUserDTO;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Hashing\HashManager;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuthService
{
    public function __construct(
        protected StatefulGuard $auth,
        protected HashManager $hash,
    ) {}

    public function register(RegisterUserDTO $dto): User
    {
        return DB::transaction(function () use ($dto) {
            $user = User::create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => $this->hash->make($dto->password),
            ]);

            $role = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
            $user->assignRole($role);

            $this->assignDefaultPermissions($user);

            return $user;
        });
    }

    public function authenticate(LoginUserDTO $dto): AuthenticatedUserDTO
    {
        if (! $this->auth->attempt($dto->toArray())) {
            throw new AuthenticationException('The provided credentials are incorrect.');
        }

        $user = $this->auth->user();
        $token = $user->createToken('auth-token')->plainTextToken;

        return new AuthenticatedUserDTO(
            userId: $user->id,
            token: $token,
            roles: $user->roles->pluck('name')->toArray(),
            permissions: $user->permissions->pluck('name')->toArray(),
        );
    }

    public function getUser(): ?User
    {
        return $this->auth->user();
    }

    public function logout(): void
    {
        $this->auth->user()?->tokens()->delete();
        $this->auth->logout();
    }

    protected function assignDefaultPermissions(User $user): void
    {
        $permissions = Permission::whereIn('name', [
            'view servers',
            'view players',
        ])->get();

        $user->permissions()->syncWithoutDetaching($permissions);
    }
}
