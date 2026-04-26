<?php

use App\Domain\Tenancy\Services\TenancyService;
use App\Models\DiscordIntegration;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
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
