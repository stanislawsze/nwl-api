<?php

use App\Domain\Tenancy\Services\TenancyService;
use App\Models\User;
use App\Policies\Domain\Tenancy\TenantPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows listing tenants for an authenticated user', function (): void {
    $user = User::factory()->create();

    expect((new TenantPolicy)->viewAny($user))->toBeTrue();
});

it('allows viewing and switching a tenant the user belongs to', function (): void {
    $user = User::factory()->create();
    $tenant = app(TenancyService::class)->ensurePersonalTenant($user);
    $policy = new TenantPolicy;

    expect($policy->view($user, $tenant))->toBeTrue();
    expect($policy->switch($user, $tenant))->toBeTrue();
});

it('denies viewing and switching a tenant the user does not belong to', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $tenant = app(TenancyService::class)->ensurePersonalTenant($other);
    $policy = new TenantPolicy;

    expect($policy->view($user, $tenant))->toBeFalse();
    expect($policy->switch($user, $tenant))->toBeFalse();
});
