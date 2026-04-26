<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\Tenancy\Services\TenancyService;
use BackedEnum;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'current_tenant_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function currentTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'current_tenant_id');
    }

    /**
     * @return BelongsToMany<Tenant, $this>
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Tenant, $this>
     */
    public function ownedTenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'owner_user_id');
    }

    /**
     * @return HasOne<DiscordAccount, $this>
     */
    public function discordAccount(): HasOne
    {
        return $this->hasOne(DiscordAccount::class);
    }

    /**
     * @return HasMany<DiscordIntegration, $this>
     */
    public function ownedDiscordIntegrations(): HasMany
    {
        return $this->hasMany(DiscordIntegration::class, 'owner_user_id')
            ->where('tenant_id', $this->current_tenant_id);
    }

    public function currentTenantOrFail(): Tenant
    {
        $tenant = $this->currentTenant;

        if ($tenant === null) {
            $tenant = app(TenancyService::class)->ensurePersonalTenant($this);
        }

        return $tenant;
    }

    public function currentTenantMembershipRole(): ?string
    {
        return $this->tenantMembershipRole($this->currentTenantOrFail());
    }

    public function tenantMembershipRole(Tenant $tenant): ?string
    {
        $membership = $this->tenants()
            ->whereKey($tenant->id)
            ->first();

        $role = $membership?->pivot?->getAttribute('role');

        return is_string($role) ? $role : null;
    }

    public function hasTenantRole(BackedEnum|int|string|Model $role, ?Tenant $tenant = null): bool
    {
        $tenant = $tenant ?? $this->currentTenantOrFail();
        $membershipRole = $this->tenantMembershipRole($tenant);
        $roleName = $this->resolveRoleName($role);

        return $membershipRole !== null && $membershipRole === $roleName;
    }

    /**
     * @param  array<int, BackedEnum|int|string|Model>  $roles
     */
    public function hasTenantAnyRole(array $roles, ?Tenant $tenant = null): bool
    {
        foreach ($roles as $role) {
            if ($this->hasTenantRole($role, $tenant)) {
                return true;
            }
        }

        return false;
    }

    public function hasTenantPermission(string $permission, ?Tenant $tenant = null): bool
    {
        $tenant = $tenant ?? $this->currentTenantOrFail();
        $membershipRole = $this->tenantMembershipRole($tenant);

        if ($membershipRole !== null) {
            $rolePermissions = config('tenancy.membership_roles.' . $membershipRole, []);

            if (is_array($rolePermissions) && in_array($permission, $rolePermissions, true)) {
                return true;
            }
        }

        try {
            return $this->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function hasCurrentTenantAccessTo(User $targetUser): bool
    {
        return $this->currentTenantOrFail()->users()
            ->whereKey($targetUser->id)
            ->exists();
    }

    protected function resolveRoleName(BackedEnum|int|string|Model $role): string
    {
        if ($role instanceof BackedEnum) {
            return (string) $role->value;
        }

        if ($role instanceof Model) {
            $name = $role->getAttribute('name');

            return is_string($name) ? $name : '';
        }

        return (string) $role;
    }
}
