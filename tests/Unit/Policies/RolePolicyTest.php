<?php

use App\Domain\Tenancy\Services\TenancyService;
use App\Models\User;
use App\Policies\Domain\Auth\RolePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'view roles']);
    Permission::firstOrCreate(['name' => 'create roles']);
    Permission::firstOrCreate(['name' => 'edit roles']);
    Permission::firstOrCreate(['name' => 'delete roles']);
});

it('allows viewAny with view roles permission', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('view roles');

    expect(rolePolicy()->viewAny($user))->toBeTrue();
});

it('allows viewAny for a tenant owner without explicit global permissions', function (): void {
    $user = User::factory()->create();
    app(TenancyService::class)->ensurePersonalTenant($user);

    expect(rolePolicy()->viewAny($user))->toBeTrue();
});

it('denies viewAny without view roles permission', function (): void {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $tenant = app(TenancyService::class)->ensurePersonalTenant($owner);
    app(TenancyService::class)->assignUserToTenant($tenant, $user, 'member');
    app(TenancyService::class)->switchTenant($user, $tenant);

    expect(rolePolicy()->viewAny($user))->toBeFalse();
});

it('allows view with view roles permission', function (): void {
    $user = User::factory()->create();
    $role = createRole('test-role');
    $user->givePermissionTo('view roles');

    expect(rolePolicy()->view($user, $role))->toBeTrue();
});

it('denies view without view roles permission', function (): void {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $role = createRole('test-role');
    $tenant = app(TenancyService::class)->ensurePersonalTenant($owner);
    app(TenancyService::class)->assignUserToTenant($tenant, $user, 'member');
    app(TenancyService::class)->switchTenant($user, $tenant);

    expect(rolePolicy()->view($user, $role))->toBeFalse();
});

it('allows create with create roles permission', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('create roles');

    expect(rolePolicy()->create($user))->toBeTrue();
});

it('denies create without create roles permission', function (): void {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $tenant = app(TenancyService::class)->ensurePersonalTenant($owner);
    app(TenancyService::class)->assignUserToTenant($tenant, $user, 'member');
    app(TenancyService::class)->switchTenant($user, $tenant);

    expect(rolePolicy()->create($user))->toBeFalse();
});

it('allows update with edit roles permission', function (): void {
    $user = User::factory()->create();
    $role = createRole('test-role');
    $user->givePermissionTo('edit roles');

    expect(rolePolicy()->update($user, $role))->toBeTrue();
});

it('denies update without edit roles permission', function (): void {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $role = createRole('test-role');
    $tenant = app(TenancyService::class)->ensurePersonalTenant($owner);
    app(TenancyService::class)->assignUserToTenant($tenant, $user, 'member');
    app(TenancyService::class)->switchTenant($user, $tenant);

    expect(rolePolicy()->update($user, $role))->toBeFalse();
});

it('allows delete with delete roles permission', function (): void {
    $user = User::factory()->create();
    $role = createRole('test-role');
    $user->givePermissionTo('delete roles');

    expect(rolePolicy()->delete($user, $role))->toBeTrue();
});

it('denies delete without delete roles permission', function (): void {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $role = createRole('test-role');
    $tenant = app(TenancyService::class)->ensurePersonalTenant($owner);
    app(TenancyService::class)->assignUserToTenant($tenant, $user, 'member');
    app(TenancyService::class)->switchTenant($user, $tenant);

    expect(rolePolicy()->delete($user, $role))->toBeFalse();
});

function rolePolicy(): RolePolicy
{
    return new RolePolicy;
}

function createRole(string $name): Role
{
    $role = new Role;
    $role->name = $name;
    $role->guard_name = 'web';
    $role->save();

    return $role;
}
