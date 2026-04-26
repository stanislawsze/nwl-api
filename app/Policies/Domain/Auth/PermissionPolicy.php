<?php

namespace App\Policies\Domain\Auth;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasTenantPermission('view permissions');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->hasTenantPermission('view permissions');
    }

    public function create(User $user): bool
    {
        return $user->hasTenantPermission('create permissions');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->hasTenantPermission('edit permissions');
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->hasTenantPermission('delete permissions');
    }
}
