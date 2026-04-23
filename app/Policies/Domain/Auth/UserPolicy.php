<?php

namespace App\Policies\Domain\Auth;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view users');
    }

    public function view(User $user, User $targetUser): bool
    {
        return $user->hasPermissionTo('view users') || $user->id === $targetUser->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create users');
    }

    public function update(User $user, User $targetUser): bool
    {
        return $user->hasPermissionTo('edit users') || $user->id === $targetUser->id;
    }

    public function delete(User $user, User $targetUser): bool
    {
        return $user->hasPermissionTo('delete users');
    }
}
