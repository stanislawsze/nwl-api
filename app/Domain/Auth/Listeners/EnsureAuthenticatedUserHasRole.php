<?php

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\UserAuthenticated;
use Spatie\Permission\Models\Role;

class EnsureAuthenticatedUserHasRole
{
    public function handle(UserAuthenticated $event): void
    {
        if ($event->user->roles()->exists()) {
            return;
        }

        $role = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $event->user->assignRole($role);
    }
}
