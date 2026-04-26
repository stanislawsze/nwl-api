<?php

namespace App\Policies\Domain\Auth;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasTenantPermission('view users');
    }

    public function view(User $user, User $targetUser): bool
    {
        if ($user->id === $targetUser->id) {
            return true;
        }

        if (! $user->hasCurrentTenantAccessTo($targetUser)) {
            return false;
        }

        return $user->hasTenantPermission('view users');
    }

    public function create(User $user): bool
    {
        return $user->hasTenantPermission('create users');
    }

    public function update(User $user, User $targetUser): bool
    {
        if ($user->id === $targetUser->id) {
            return true;
        }

        if (! $user->hasCurrentTenantAccessTo($targetUser)) {
            return false;
        }

        return $user->hasTenantPermission('edit users');
    }

    public function delete(User $user, User $targetUser): bool
    {
        return $user->hasCurrentTenantAccessTo($targetUser)
            && $user->hasTenantPermission('delete users');
    }
}
