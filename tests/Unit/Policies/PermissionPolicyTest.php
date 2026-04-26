<?php

use App\Domain\Tenancy\Services\TenancyService;
use App\Models\User;
use App\Policies\Domain\Auth\PermissionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'view permissions']);
    Permission::firstOrCreate(['name' => 'create permissions']);
    Permission::firstOrCreate(['name' => 'edit permissions']);
    Permission::firstOrCreate(['name' => 'delete permissions']);
});

it('allows viewAny with view permissions permission', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('view permissions');

    expect(permissionPolicy()->viewAny($user))->toBeTrue();
});

it('allows viewAny for a tenant owner without explicit global permissions', function (): void {
    $user = User::factory()->create();
    app(TenancyService::class)->ensurePersonalTenant($user);

    expect(permissionPolicy()->viewAny($user))->toBeTrue();
});

it('denies viewAny without view permissions permission', function (): void {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $tenant = app(TenancyService::class)->ensurePersonalTenant($owner);
    app(TenancyService::class)->assignUserToTenant($tenant, $user, 'member');
    app(TenancyService::class)->switchTenant($user, $tenant);

    expect(permissionPolicy()->viewAny($user))->toBeFalse();
});

it('allows view with view permissions permission', function (): void {
    $user = User::factory()->create();
    $permission = createPermission('test-permission');
    $user->givePermissionTo('view permissions');

    expect(permissionPolicy()->view($user, $permission))->toBeTrue();
});

it('denies view without view permissions permission', function (): void {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $permission = createPermission('test-permission');
    $tenant = app(TenancyService::class)->ensurePersonalTenant($owner);
    app(TenancyService::class)->assignUserToTenant($tenant, $user, 'member');
    app(TenancyService::class)->switchTenant($user, $tenant);

    expect(permissionPolicy()->view($user, $permission))->toBeFalse();
});

it('allows create with create permissions permission', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('create permissions');

    expect(permissionPolicy()->create($user))->toBeTrue();
});

it('denies create without create permissions permission', function (): void {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $tenant = app(TenancyService::class)->ensurePersonalTenant($owner);
    app(TenancyService::class)->assignUserToTenant($tenant, $user, 'member');
    app(TenancyService::class)->switchTenant($user, $tenant);

    expect(permissionPolicy()->create($user))->toBeFalse();
});

it('allows update with edit permissions permission', function (): void {
    $user = User::factory()->create();
    $permission = createPermission('test-permission');
    $user->givePermissionTo('edit permissions');

    expect(permissionPolicy()->update($user, $permission))->toBeTrue();
});

it('denies update without edit permissions permission', function (): void {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $permission = createPermission('test-permission');
    $tenant = app(TenancyService::class)->ensurePersonalTenant($owner);
    app(TenancyService::class)->assignUserToTenant($tenant, $user, 'member');
    app(TenancyService::class)->switchTenant($user, $tenant);

    expect(permissionPolicy()->update($user, $permission))->toBeFalse();
});

it('allows delete with delete permissions permission', function (): void {
    $user = User::factory()->create();
    $permission = createPermission('test-permission');
    $user->givePermissionTo('delete permissions');

    expect(permissionPolicy()->delete($user, $permission))->toBeTrue();
});

it('denies delete without delete permissions permission', function (): void {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $permission = createPermission('test-permission');
    $tenant = app(TenancyService::class)->ensurePersonalTenant($owner);
    app(TenancyService::class)->assignUserToTenant($tenant, $user, 'member');
    app(TenancyService::class)->switchTenant($user, $tenant);

    expect(permissionPolicy()->delete($user, $permission))->toBeFalse();
});

function permissionPolicy(): PermissionPolicy
{
    return new PermissionPolicy;
}

function createPermission(string $name): Permission
{
    $permission = new Permission;
    $permission->name = $name;
    $permission->guard_name = 'web';
    $permission->save();

    return $permission;
}
