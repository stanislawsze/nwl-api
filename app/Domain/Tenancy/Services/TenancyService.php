<?php

namespace App\Domain\Tenancy\Services;

use App\Models\Tenant;
use App\Models\User;
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
