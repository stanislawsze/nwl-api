<?php

namespace App\Policies\Domain\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TenantPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->id > 0;
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $user->tenants()->whereKey($tenant->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->id > 0;
    }

    public function switch(User $user, Tenant $tenant): bool
    {
        return $user->tenants()->whereKey($tenant->id)->exists();
    }

    public function viewMembers(User $user, Tenant $tenant): bool
    {
        return $user->tenants()->whereKey($tenant->id)->exists()
            && $user->hasTenantPermission('view users', $tenant);
    }

    public function addMember(User $user, Tenant $tenant): bool
    {
        return $user->tenants()->whereKey($tenant->id)->exists()
            && $user->hasTenantPermission('create users', $tenant);
    }

    public function updateMember(User $user, Tenant $tenant): bool
    {
        return $user->tenants()->whereKey($tenant->id)->exists()
            && $user->hasTenantPermission('edit users', $tenant);
    }

    public function removeMember(User $user, Tenant $tenant): bool
    {
        return $user->tenants()->whereKey($tenant->id)->exists()
            && $user->hasTenantPermission('delete users', $tenant);
    }
}
