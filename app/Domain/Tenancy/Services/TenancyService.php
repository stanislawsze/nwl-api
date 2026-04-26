<?php

namespace App\Domain\Tenancy\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TenancyService
{
    public function ensurePersonalTenant(User $user): Tenant
    {
        if ($user->currentTenant !== null) {
            return $user->currentTenant;
        }

        $ownedTenant = $user->ownedTenants()->first();

        if ($ownedTenant !== null) {
            $user->forceFill([
                'current_tenant_id' => $ownedTenant->id,
            ])->save();

            return $ownedTenant;
        }

        return DB::transaction(function () use ($user) {
            $tenant = Tenant::query()->create([
                'name' => $user->name . "'s Workspace",
                'slug' => $this->uniqueSlug($user->name),
                'owner_user_id' => $user->id,
            ]);

            $tenant->users()->attach($user->id, [
                'role' => 'owner',
            ]);

            $user->forceFill([
                'current_tenant_id' => $tenant->id,
            ])->save();

            return $tenant;
        });
    }

    public function createTenant(User $user, string $name): Tenant
    {
        return DB::transaction(function () use ($user, $name) {
            $tenant = Tenant::query()->create([
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'owner_user_id' => $user->id,
            ]);

            $tenant->users()->attach($user->id, [
                'role' => 'owner',
            ]);

            $user->forceFill([
                'current_tenant_id' => $tenant->id,
            ])->save();

            return $tenant->refresh();
        });
    }

    /**
     * @return Collection<int, Tenant>
     */
    public function tenantsForUser(User $user): Collection
    {
        $this->ensurePersonalTenant($user);

        return $user->tenants()
            ->with('owner')
            ->orderBy('name')
            ->get();
    }

    public function switchTenant(User $user, Tenant $tenant): Tenant
    {
        $isMember = $user->tenants()
            ->whereKey($tenant->id)
            ->exists();

        if (! $isMember) {
            throw new AuthorizationException('You do not belong to this tenant.');
        }

        $user->forceFill([
            'current_tenant_id' => $tenant->id,
        ])->save();

        return $tenant->refresh();
    }

    public function assignUserToTenant(Tenant $tenant, User $user, string $role = 'member'): void
    {
        $tenant->users()->syncWithoutDetaching([
            $user->id => [
                'role' => $role,
            ],
        ]);
    }

    /**
     * @return Collection<int, User>
     */
    public function membersForTenant(Tenant $tenant): Collection
    {
        return $tenant->users()
            ->orderBy('users.name')
            ->get();
    }

    public function addMemberByEmail(User $actor, string $email, string $role): User
    {
        $tenant = $actor->currentTenantOrFail();
        $this->assertValidMembershipRole($role);

        $member = User::query()
            ->where('email', $email)
            ->first();

        if ($member === null) {
            throw ValidationException::withMessages([
                'email' => ['The selected user could not be found.'],
            ]);
        }

        if ($tenant->users()->whereKey($member->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['This user already belongs to the current tenant.'],
            ]);
        }

        $this->assignUserToTenant($tenant, $member, $role);

        return $tenant->users()->findOrFail($member->id);
    }

    public function updateMemberRole(User $actor, User $member, string $role): User
    {
        $tenant = $actor->currentTenantOrFail();
        $this->assertValidMembershipRole($role);

        if (! $tenant->users()->whereKey($member->id)->exists()) {
            throw ValidationException::withMessages([
                'user' => ['The selected user is not a member of the current tenant.'],
            ]);
        }

        if ($tenant->owner_user_id === $member->id) {
            throw ValidationException::withMessages([
                'user' => ['The tenant owner role cannot be changed.'],
            ]);
        }

        if ($actor->is($member)) {
            throw ValidationException::withMessages([
                'user' => ['You cannot change your own tenant role.'],
            ]);
        }

        $tenant->users()->updateExistingPivot($member->id, [
            'role' => $role,
        ]);

        return $tenant->users()->findOrFail($member->id);
    }

    public function removeMember(User $actor, User $member): void
    {
        $tenant = $actor->currentTenantOrFail();

        if (! $tenant->users()->whereKey($member->id)->exists()) {
            throw ValidationException::withMessages([
                'user' => ['The selected user is not a member of the current tenant.'],
            ]);
        }

        if ($tenant->owner_user_id === $member->id) {
            throw ValidationException::withMessages([
                'user' => ['The tenant owner cannot be removed.'],
            ]);
        }

        if ($actor->is($member)) {
            throw ValidationException::withMessages([
                'user' => ['You cannot remove yourself from the current tenant.'],
            ]);
        }

        DB::transaction(function () use ($tenant, $member): void {
            $tenant->users()->detach($member->id);

            if ($member->current_tenant_id === $tenant->id) {
                $nextTenantId = $member->tenants()
                    ->whereKeyNot($tenant->id)
                    ->value('tenants.id');

                $member->forceFill([
                    'current_tenant_id' => $nextTenantId,
                ])->save();
            }
        });
    }

    protected function assertValidMembershipRole(string $role): void
    {
        if (array_key_exists($role, config('tenancy.membership_roles', []))) {
            return;
        }

        throw ValidationException::withMessages([
            'role' => ['The selected tenant role is invalid.'],
        ]);
    }

    protected function uniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug !== '' ? $baseSlug : 'tenant';
        $counter = 1;

        while (Tenant::query()->where('slug', $slug)->exists()) {
            $counter++;
            $slug = sprintf('%s-%d', $baseSlug !== '' ? $baseSlug : 'tenant', $counter);
        }

        return $slug;
    }
}
