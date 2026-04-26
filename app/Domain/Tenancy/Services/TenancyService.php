<?php

namespace App\Domain\Tenancy\Services;

use App\Models\Tenant;
use App\Models\TenantInvitation;
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

    /**
     * @return Collection<int, TenantInvitation>
     */
    public function invitationsForTenant(Tenant $tenant): Collection
    {
        return $tenant->invitations()
            ->latest()
            ->get();
    }

    public function createInvitation(User $actor, string $email, string $role, ?int $expiresInHours = null): TenantInvitation
    {
        $tenant = $actor->currentTenantOrFail();
        $this->assertValidMembershipRole($role);

        if ($tenant->users()->where('users.email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['This user already belongs to the current tenant.'],
            ]);
        }

        $existingPendingInvitation = $tenant->invitations()
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->when(
                true,
                fn ($query) => $query->where(function ($query): void {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                }),
            )
            ->exists();

        if ($existingPendingInvitation) {
            throw ValidationException::withMessages([
                'email' => ['A pending invitation already exists for this email address.'],
            ]);
        }

        return TenantInvitation::query()->create([
            'tenant_id' => $tenant->id,
            'email' => $email,
            'role' => $role,
            'token' => (string) Str::uuid(),
            'invited_by_user_id' => $actor->id,
            'expires_at' => $expiresInHours !== null ? now()->addHours($expiresInHours) : now()->addDays(7),
        ]);
    }

    public function acceptInvitation(User $user, string $token): TenantInvitation
    {
        $invitation = TenantInvitation::query()
            ->where('token', $token)
            ->first();

        if ($invitation === null || ! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'token' => ['The tenant invitation is invalid or no longer available.'],
            ]);
        }

        if (strtolower($invitation->email) !== strtolower($user->email)) {
            throw ValidationException::withMessages([
                'email' => ['This invitation does not belong to the authenticated user.'],
            ]);
        }

        return DB::transaction(function () use ($user, $invitation) {
            $tenant = $invitation->tenant()->firstOrFail();

            if (! $tenant->users()->whereKey($user->id)->exists()) {
                $this->assignUserToTenant($tenant, $user, $invitation->role);
            }

            $invitation->forceFill([
                'accepted_by_user_id' => $user->id,
                'accepted_at' => now(),
            ])->save();

            $user->forceFill([
                'current_tenant_id' => $tenant->id,
            ])->save();

            return $invitation->refresh();
        });
    }

    public function revokeInvitation(User $actor, TenantInvitation $invitation): void
    {
        $tenant = $actor->currentTenantOrFail();

        if ((int) $invitation->tenant_id !== (int) $tenant->id) {
            throw ValidationException::withMessages([
                'tenant_invitation' => ['The selected tenant invitation is invalid.'],
            ]);
        }

        if (! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'tenant_invitation' => ['Only pending invitations can be revoked.'],
            ]);
        }

        $invitation->forceFill([
            'revoked_at' => now(),
        ])->save();
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
