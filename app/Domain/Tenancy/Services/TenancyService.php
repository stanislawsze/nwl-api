<?php

namespace App\Domain\Tenancy\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
