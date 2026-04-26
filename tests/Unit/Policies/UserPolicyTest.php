<?php

use App\Domain\Tenancy\Services\TenancyService;
use App\Models\User;
use App\Policies\Domain\Auth\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'view users']);
    Permission::firstOrCreate(['name' => 'create users']);
    Permission::firstOrCreate(['name' => 'edit users']);
    Permission::firstOrCreate(['name' => 'delete users']);
});

it('allows viewAny with view users permission', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('view users');

    expect(userPolicy()->viewAny($user))->toBeTrue();
});

it('allows viewAny for a tenant owner without explicit global permissions', function (): void {
    $user = User::factory()->create();
    app(TenancyService::class)->ensurePersonalTenant($user);

    expect(userPolicy()->viewAny($user))->toBeTrue();
});

it('denies viewAny without view users permission', function (): void {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $tenant = app(TenancyService::class)->ensurePersonalTenant($owner);
    app(TenancyService::class)->assignUserToTenant($tenant, $user, 'member');
    app(TenancyService::class)->switchTenant($user, $tenant);

    expect(userPolicy()->viewAny($user))->toBeFalse();
});

it('allows view with view users permission', function (): void {
    $user = User::factory()->create();
    $targetUser = User::factory()->create();
    $tenant = app(TenancyService::class)->ensurePersonalTenant($user);
    app(TenancyService::class)->assignUserToTenant($tenant, $targetUser, 'member');
    app(TenancyService::class)->switchTenant($targetUser, $tenant);
    $user->givePermissionTo('view users');

    expect(userPolicy()->view($user, $targetUser))->toBeTrue();
});

it('allows viewing own profile', function (): void {
    $user = User::factory()->create();

    expect(userPolicy()->view($user, $user))->toBeTrue();
});

it('denies viewing another user without permission', function (): void {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $targetUser = User::factory()->create();
    $tenant = app(TenancyService::class)->ensurePersonalTenant($owner);
    app(TenancyService::class)->assignUserToTenant($tenant, $user, 'member');
    app(TenancyService::class)->switchTenant($user, $tenant);
    app(TenancyService::class)->assignUserToTenant($tenant, $targetUser, 'member');
    app(TenancyService::class)->switchTenant($targetUser, $tenant);

    expect(userPolicy()->view($user, $targetUser))->toBeFalse();
});

it('denies viewing a user from another tenant even with global permission', function (): void {
    $user = User::factory()->create();
    $targetUser = User::factory()->create();
    app(TenancyService::class)->ensurePersonalTenant($user);
    app(TenancyService::class)->ensurePersonalTenant($targetUser);
    $user->givePermissionTo('view users');

    expect(userPolicy()->view($user, $targetUser))->toBeFalse();
});

it('allows create with create users permission', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('create users');

    expect(userPolicy()->create($user))->toBeTrue();
});

it('denies create without create users permission', function (): void {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $tenant = app(TenancyService::class)->ensurePersonalTenant($owner);
    app(TenancyService::class)->assignUserToTenant($tenant, $user, 'member');
    app(TenancyService::class)->switchTenant($user, $tenant);

    expect(userPolicy()->create($user))->toBeFalse();
});

it('allows update with edit users permission', function (): void {
    $user = User::factory()->create();
    $targetUser = User::factory()->create();
    $tenant = app(TenancyService::class)->ensurePersonalTenant($user);
    app(TenancyService::class)->assignUserToTenant($tenant, $targetUser, 'member');
    app(TenancyService::class)->switchTenant($targetUser, $tenant);
    $user->givePermissionTo('edit users');

    expect(userPolicy()->update($user, $targetUser))->toBeTrue();
});

it('allows updating own profile', function (): void {
    $user = User::factory()->create();

    expect(userPolicy()->update($user, $user))->toBeTrue();
});

it('denies updating another user without permission', function (): void {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $targetUser = User::factory()->create();
    $tenant = app(TenancyService::class)->ensurePersonalTenant($owner);
    app(TenancyService::class)->assignUserToTenant($tenant, $user, 'member');
    app(TenancyService::class)->switchTenant($user, $tenant);
    app(TenancyService::class)->assignUserToTenant($tenant, $targetUser, 'member');
    app(TenancyService::class)->switchTenant($targetUser, $tenant);

    expect(userPolicy()->update($user, $targetUser))->toBeFalse();
});

it('denies updating a user from another tenant even with global permission', function (): void {
    $user = User::factory()->create();
    $targetUser = User::factory()->create();
    app(TenancyService::class)->ensurePersonalTenant($user);
    app(TenancyService::class)->ensurePersonalTenant($targetUser);
    $user->givePermissionTo('edit users');

    expect(userPolicy()->update($user, $targetUser))->toBeFalse();
});

it('allows delete with delete users permission', function (): void {
    $user = User::factory()->create();
    $targetUser = User::factory()->create();
    $tenant = app(TenancyService::class)->ensurePersonalTenant($user);
    app(TenancyService::class)->assignUserToTenant($tenant, $targetUser, 'member');
    app(TenancyService::class)->switchTenant($targetUser, $tenant);
    $user->givePermissionTo('delete users');

    expect(userPolicy()->delete($user, $targetUser))->toBeTrue();
});

it('denies delete without delete users permission', function (): void {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $targetUser = User::factory()->create();
    $tenant = app(TenancyService::class)->ensurePersonalTenant($owner);
    app(TenancyService::class)->assignUserToTenant($tenant, $user, 'member');
    app(TenancyService::class)->switchTenant($user, $tenant);
    app(TenancyService::class)->assignUserToTenant($tenant, $targetUser, 'member');
    app(TenancyService::class)->switchTenant($targetUser, $tenant);

    expect(userPolicy()->delete($user, $targetUser))->toBeFalse();
});

it('denies deleting a user from another tenant even with global permission', function (): void {
    $user = User::factory()->create();
    $targetUser = User::factory()->create();
    app(TenancyService::class)->ensurePersonalTenant($user);
    app(TenancyService::class)->ensurePersonalTenant($targetUser);
    $user->givePermissionTo('delete users');

    expect(userPolicy()->delete($user, $targetUser))->toBeFalse();
});

function userPolicy(): UserPolicy
{
    return new UserPolicy;
}
