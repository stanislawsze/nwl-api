<?php

namespace App\Providers;

use App\Models\Tenant;
use App\Models\User;
use App\Policies\Domain\Auth\PermissionPolicy;
use App\Policies\Domain\Auth\RolePolicy;
use App\Policies\Domain\Auth\UserPolicy;
use App\Policies\Domain\Tenancy\TenantPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Tenant::class, TenantPolicy::class);
    }
}
