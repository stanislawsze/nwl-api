<?php

namespace App\Policies\Domain\Auth;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasTenantPermission('view roles');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasTenantPermission('view roles');
    }

    public function create(User $user): bool
    {
        return $user->hasTenantPermission('create roles');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasTenantPermission('edit roles');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->hasTenantPermission('delete roles');
    }
}
