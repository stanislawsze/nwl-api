<?php

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

it('denies viewAny without view users permission', function (): void {
    $user = User::factory()->create();

    expect(userPolicy()->viewAny($user))->toBeFalse();
});

it('allows view with view users permission', function (): void {
    $user = User::factory()->create();
    $targetUser = User::factory()->create();
    $user->givePermissionTo('view users');

    expect(userPolicy()->view($user, $targetUser))->toBeTrue();
});

it('allows viewing own profile', function (): void {
    $user = User::factory()->create();

    expect(userPolicy()->view($user, $user))->toBeTrue();
});

it('denies viewing another user without permission', function (): void {
    $user = User::factory()->create();
    $targetUser = User::factory()->create();

    expect(userPolicy()->view($user, $targetUser))->toBeFalse();
});

it('allows create with create users permission', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('create users');

    expect(userPolicy()->create($user))->toBeTrue();
});

it('denies create without create users permission', function (): void {
    $user = User::factory()->create();

    expect(userPolicy()->create($user))->toBeFalse();
});

it('allows update with edit users permission', function (): void {
    $user = User::factory()->create();
    $targetUser = User::factory()->create();
    $user->givePermissionTo('edit users');

    expect(userPolicy()->update($user, $targetUser))->toBeTrue();
});

it('allows updating own profile', function (): void {
    $user = User::factory()->create();

    expect(userPolicy()->update($user, $user))->toBeTrue();
});

it('denies updating another user without permission', function (): void {
    $user = User::factory()->create();
    $targetUser = User::factory()->create();

    expect(userPolicy()->update($user, $targetUser))->toBeFalse();
});

it('allows delete with delete users permission', function (): void {
    $user = User::factory()->create();
    $targetUser = User::factory()->create();
    $user->givePermissionTo('delete users');

    expect(userPolicy()->delete($user, $targetUser))->toBeTrue();
});

it('denies delete without delete users permission', function (): void {
    $user = User::factory()->create();
    $targetUser = User::factory()->create();

    expect(userPolicy()->delete($user, $targetUser))->toBeFalse();
});

function userPolicy(): UserPolicy
{
    return new UserPolicy;
}
