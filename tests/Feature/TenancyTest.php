<?php

use App\Domain\Tenancy\Services\TenancyService;
use App\Models\DiscordIntegration;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

it('creates a personal tenant when a user registers', function (): void {
    $user = User::factory()->create([
        'current_tenant_id' => null,
    ]);

    app(TenancyService::class)->ensurePersonalTenant($user);

    $user->refresh();

    expect($user->current_tenant_id)->not->toBeNull();
    expect(Tenant::query()->whereKey($user->current_tenant_id)->where('owner_user_id', $user->id)->exists())->toBeTrue();
});

it('scopes discord integrations to the current tenant', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $ownerTenant = app(TenancyService::class)->ensurePersonalTenant($owner);
    $otherTenant = app(TenancyService::class)->ensurePersonalTenant($other);

    DiscordIntegration::query()->create([
        'tenant_id' => $ownerTenant->id,
        'owner_user_id' => $owner->id,
        'guild_id' => 'guild-owner',
        'guild_name' => 'Owner Guild',
        'bot_enabled' => false,
        'is_active' => true,
        'settings' => [],
    ]);

    DiscordIntegration::query()->create([
        'tenant_id' => $otherTenant->id,
        'owner_user_id' => $other->id,
        'guild_id' => 'guild-other',
        'guild_name' => 'Other Guild',
        'bot_enabled' => false,
        'is_active' => true,
        'settings' => [],
    ]);

    Sanctum::actingAs($owner);

    getJson('/api/v1/discord/integration')->assertOk()
        ->assertJsonPath('data.guild_id', 'guild-owner');

    Sanctum::actingAs($other);

    getJson('/api/v1/discord/integration')->assertOk()
        ->assertJsonPath('data.guild_id', 'guild-other');
});

it('prevents cross-tenant updates to a discord integration', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $ownerTenant = app(TenancyService::class)->ensurePersonalTenant($owner);
    app(TenancyService::class)->ensurePersonalTenant($other);

    $integration = DiscordIntegration::query()->create([
        'tenant_id' => $ownerTenant->id,
        'owner_user_id' => $owner->id,
        'guild_id' => 'guild-owner',
        'guild_name' => 'Owner Guild',
        'bot_enabled' => false,
        'is_active' => true,
        'settings' => [],
    ]);

    $otherToken = $other->createToken('other')->plainTextToken;

    putJson('/api/v1/discord/integration', [
        'guild_id' => 'guild-other',
        'guild_name' => 'Other Guild',
        'bot_enabled' => false,
    ], [
        'Authorization' => 'Bearer ' . $otherToken,
    ])->assertOk();

    expect($integration->fresh()->guild_id)->toBe('guild-owner');
});

it('lists the authenticated user tenants and marks the current one', function (): void {
    $user = User::factory()->create([
        'name' => 'Zed User',
    ]);
    $service = app(TenancyService::class);
    $personalTenant = $service->ensurePersonalTenant($user);
    $secondTenant = $service->createTenant($user, 'Operations Workspace');
    $service->switchTenant($user, $personalTenant);

    $token = $user->createToken('tenant-list')->plainTextToken;

    getJson('/api/v1/tenants', [
        'Authorization' => 'Bearer ' . $token,
    ])->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.membership_role', 'owner')
        ->assertJsonPath('data.0.is_current', false)
        ->assertJsonPath('data.1.is_current', true);

    expect($secondTenant->id)->not->toBe($personalTenant->id);
});

it('creates a new tenant and switches the current context to it', function (): void {
    $user = User::factory()->create();
    app(TenancyService::class)->ensurePersonalTenant($user);
    $token = $user->createToken('tenant-create')->plainTextToken;

    postJson('/api/v1/tenants', [
        'name' => 'Moderation Team',
    ], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertCreated()
        ->assertJsonPath('data.name', 'Moderation Team')
        ->assertJsonPath('data.membership_role', 'owner')
        ->assertJsonPath('meta.message', 'Tenant created successfully.');

    $user->refresh();
    $tenant = Tenant::query()->where('name', 'Moderation Team')->firstOrFail();

    expect($user->current_tenant_id)->toBe($tenant->id);
    expect($user->tenants()->whereKey($tenant->id)->exists())->toBeTrue();
});

it('switches to another tenant the user belongs to', function (): void {
    $user = User::factory()->create();
    $service = app(TenancyService::class);
    $originalTenant = $service->ensurePersonalTenant($user);
    $secondTenant = $service->createTenant($user, 'Support Workspace');
    $token = $user->createToken('tenant-switch')->plainTextToken;

    postJson('/api/v1/tenants/' . $originalTenant->id . '/switch', [], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertOk()
        ->assertJsonPath('data.id', $originalTenant->id)
        ->assertJsonPath('data.is_current', true)
        ->assertJsonPath('meta.message', 'Tenant switched successfully.');

    expect($secondTenant->id)->not->toBe($originalTenant->id);
    expect($user->fresh()->current_tenant_id)->toBe($originalTenant->id);
});

it('forbids switching to a tenant the user does not belong to', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $service = app(TenancyService::class);
    $service->ensurePersonalTenant($user);
    $otherTenant = $service->ensurePersonalTenant($other);
    $token = $user->createToken('tenant-switch')->plainTextToken;

    postJson('/api/v1/tenants/' . $otherTenant->id . '/switch', [], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertForbidden()
        ->assertJson([
            'message' => 'Forbidden',
            'code' => 'authorization_denied',
            'errors' => [],
        ]);
});
