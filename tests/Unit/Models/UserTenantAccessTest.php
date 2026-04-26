<?php

use App\Domain\Tenancy\Services\TenancyService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resolves the current tenant membership role', function (): void {
    $user = User::factory()->create();
    $tenant = app(TenancyService::class)->ensurePersonalTenant($user);

    expect($user->currentTenantMembershipRole())->toBe('owner');
    expect($user->tenantMembershipRole($tenant))->toBe('owner');
});

it('checks tenant roles against the current tenant', function (): void {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $service = app(TenancyService::class);
    $tenant = $service->ensurePersonalTenant($owner);
    $service->assignUserToTenant($tenant, $member, 'support');
    $service->switchTenant($member, $tenant);

    expect($member->hasTenantRole('support'))->toBeTrue();
    expect($member->hasTenantAnyRole(['support', 'admin']))->toBeTrue();
    expect($member->hasTenantRole('owner'))->toBeFalse();
});

it('checks tenant permissions from the tenant membership role', function (): void {
    $owner = User::factory()->create();
    $moderator = User::factory()->create();
    $service = app(TenancyService::class);
    $tenant = $service->ensurePersonalTenant($owner);
    $service->assignUserToTenant($tenant, $moderator, 'moderator');
    $service->switchTenant($moderator, $tenant);

    expect($owner->hasTenantPermission('delete users'))->toBeTrue();
    expect($moderator->hasTenantPermission('edit users'))->toBeTrue();
    expect($moderator->hasTenantPermission('delete users'))->toBeFalse();
});

it('checks whether another user belongs to the current tenant', function (): void {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();
    $service = app(TenancyService::class);
    $tenant = $service->ensurePersonalTenant($owner);
    $service->assignUserToTenant($tenant, $member, 'member');
    $service->switchTenant($member, $tenant);

    expect($owner->hasCurrentTenantAccessTo($member))->toBeTrue();
    expect($owner->hasCurrentTenantAccessTo($outsider))->toBeFalse();
});
