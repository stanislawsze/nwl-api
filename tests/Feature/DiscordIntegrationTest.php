<?php

use App\Models\DiscordAccount;
use App\Models\DiscordIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('services.discord.client_id', 'discord-client-id');
    config()->set('services.discord.client_secret', 'discord-client-secret');
    config()->set('services.discord.redirect', 'https://panel.example.com/api/v1/auth/discord/callback');

    Role::firstOrCreate(['name' => 'user']);
    Role::firstOrCreate(['name' => 'discord-member']);
});

it('allows an authenticated user to configure a discord integration and role mappings', function (): void {
    $owner = User::factory()->create();
    $token = $owner->createToken('owner-token')->plainTextToken;
    $role = Role::query()->where('name', 'discord-member')->firstOrFail();
    $tenant = $owner->currentTenantOrFail();

    putJson('/api/v1/discord/integration', [
        'guild_id' => '123456789',
        'guild_name' => 'NWL Crew',
        'bot_enabled' => true,
        'oauth_client_id' => 'tenant-discord-client-id',
        'oauth_client_secret' => 'tenant-discord-client-secret',
        'oauth_redirect_uri' => 'https://tenant.example.com/api/v1/auth/discord/callback',
        'bot_token' => 'tenant-bot-token',
        'settings' => [
            'auto_sync_roles' => true,
        ],
    ], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertOk()
        ->assertJsonPath('data.guild_name', 'NWL Crew')
        ->assertJsonPath('data.oauth.client_id', 'tenant-discord-client-id')
        ->assertJsonPath('data.oauth.redirect_uri', 'https://tenant.example.com/api/v1/auth/discord/callback')
        ->assertJsonPath('data.oauth.has_client_secret', true)
        ->assertJsonPath('data.bot.has_token', true)
        ->assertJsonPath('data.status.is_ready_for_oauth', true)
        ->assertJsonPath('data.status.is_ready_for_bot_sync', true);

    $integration = DiscordIntegration::query()->firstOrFail();

    postJson('/api/v1/discord/role-mappings', [
        'discord_integration_id' => $integration->id,
        'discord_role_id' => '987654321',
        'discord_role_name' => 'Panel Member',
        'local_role_id' => $role->id,
    ], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertCreated()
        ->assertJsonPath('data.local_role_name', 'discord-member');

    getJson('/api/v1/discord/integration', [
        'Authorization' => 'Bearer ' . $token,
    ])->assertOk()
        ->assertJsonPath('data.role_mappings.0.discord_role_id', '987654321')
        ->assertJsonMissingPath('data.oauth.client_secret')
        ->assertJsonMissingPath('data.bot.token');
});

it('updates discord credentials without replacing the whole integration', function (): void {
    $owner = User::factory()->create();
    $token = $owner->createToken('owner-token')->plainTextToken;
    $tenant = $owner->currentTenantOrFail();

    DiscordIntegration::query()->create([
        'tenant_id' => $tenant->id,
        'owner_user_id' => $owner->id,
        'guild_id' => '123456789',
        'guild_name' => 'NWL Crew',
        'bot_enabled' => false,
        'is_active' => true,
        'settings' => [],
    ]);

    patchJson('/api/v1/discord/integration/credentials', [
        'oauth_client_id' => 'patched-client-id',
        'oauth_client_secret' => 'patched-client-secret',
        'oauth_redirect_uri' => 'https://tenant.example.com/oauth/callback',
    ], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertOk()
        ->assertJsonPath('data.oauth.client_id', 'patched-client-id')
        ->assertJsonPath('data.oauth.has_client_secret', true)
        ->assertJsonPath('data.status.is_ready_for_oauth', true)
        ->assertJsonPath('meta.message', 'Discord credentials updated.');
});

it('clears selected discord credentials', function (): void {
    $owner = User::factory()->create();
    $token = $owner->createToken('owner-token')->plainTextToken;
    $tenant = $owner->currentTenantOrFail();

    DiscordIntegration::query()->create([
        'tenant_id' => $tenant->id,
        'owner_user_id' => $owner->id,
        'guild_id' => '123456789',
        'guild_name' => 'NWL Crew',
        'bot_enabled' => true,
        'is_active' => true,
        'oauth_client_id' => 'client-id',
        'oauth_client_secret' => 'client-secret',
        'oauth_redirect_uri' => 'https://tenant.example.com/oauth/callback',
        'bot_token' => 'bot-token',
        'settings' => [],
    ]);

    deleteJson('/api/v1/discord/integration/credentials', [
        'fields' => ['oauth_client_secret', 'bot_token'],
    ], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertOk()
        ->assertJsonPath('data.oauth.has_client_secret', false)
        ->assertJsonPath('data.bot.has_token', false)
        ->assertJsonPath('data.status.is_ready_for_oauth', false)
        ->assertJsonPath('data.status.is_ready_for_bot_sync', false)
        ->assertJsonPath('meta.message', 'Discord credentials cleared.');
});

it('returns a discord authorization url for the frontend', function (): void {
    $response = getJson('/api/v1/auth/discord/redirect');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['authorization_url', 'state'],
        ]);

    expect($response->json('data.authorization_url'))->toContain('discord.com/oauth2/authorize');
});

it('lists guild roles using the configured bot token', function (): void {
    $owner = User::factory()->create();
    $token = $owner->createToken('owner-token')->plainTextToken;
    $tenant = $owner->currentTenantOrFail();

    DiscordIntegration::query()->create([
        'tenant_id' => $tenant->id,
        'owner_user_id' => $owner->id,
        'guild_id' => '123456789',
        'guild_name' => 'NWL Crew',
        'bot_enabled' => true,
        'is_active' => true,
        'bot_token' => 'persisted-bot-token',
        'settings' => [],
    ]);

    Http::fake([
        'https://discord.com/api/guilds/123456789/roles' => Http::response([
            [
                'id' => '2',
                'name' => 'Moderator',
                'position' => 20,
                'color' => 16711680,
                'managed' => false,
                'mentionable' => true,
            ],
            [
                'id' => '1',
                'name' => '@everyone',
                'position' => 0,
                'color' => 0,
                'managed' => false,
                'mentionable' => false,
            ],
        ]),
    ]);

    getJson('/api/v1/discord/guild-roles', [
        'Authorization' => 'Bearer ' . $token,
    ])->assertOk()
        ->assertJsonPath('data.0.name', 'Moderator')
        ->assertJsonPath('data.1.name', '@everyone');
});

it('fails to list guild roles when no bot token is configured', function (): void {
    $owner = User::factory()->create();
    $token = $owner->createToken('owner-token')->plainTextToken;
    $tenant = $owner->currentTenantOrFail();

    DiscordIntegration::query()->create([
        'tenant_id' => $tenant->id,
        'owner_user_id' => $owner->id,
        'guild_id' => '123456789',
        'guild_name' => 'NWL Crew',
        'bot_enabled' => true,
        'is_active' => true,
        'settings' => [],
    ]);

    getJson('/api/v1/discord/guild-roles', [
        'Authorization' => 'Bearer ' . $token,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['bot_token']);
});

it('uses persisted oauth configuration for integration-specific redirects', function (): void {
    $owner = User::factory()->create();
    $tenant = $owner->currentTenantOrFail();

    $integration = DiscordIntegration::query()->create([
        'tenant_id' => $tenant->id,
        'owner_user_id' => $owner->id,
        'guild_id' => '123456789',
        'guild_name' => 'NWL Crew',
        'bot_enabled' => true,
        'is_active' => true,
        'oauth_client_id' => 'persisted-client-id',
        'oauth_client_secret' => 'persisted-client-secret',
        'oauth_redirect_uri' => 'https://tenant.example.com/api/v1/auth/discord/callback',
        'bot_token' => 'persisted-bot-token',
        'settings' => [],
    ]);

    getJson('/api/v1/auth/discord/redirect?integration_id=' . $integration->id)
        ->assertOk()
        ->assertJsonPath('data.authorization_url', fn (string $value): bool => str_contains($value, 'client_id=persisted-client-id')
            && str_contains($value, urlencode('https://tenant.example.com/api/v1/auth/discord/callback')));
});

it('links a discord account and syncs mapped roles on callback', function (): void {
    $role = Role::query()->where('name', 'discord-member')->firstOrFail();
    $owner = User::factory()->create();
    $tenant = $owner->currentTenantOrFail();
    $integration = DiscordIntegration::query()->create([
        'tenant_id' => $tenant->id,
        'owner_user_id' => $owner->id,
        'guild_id' => '123456789',
        'guild_name' => 'NWL Crew',
        'bot_enabled' => true,
        'is_active' => true,
        'settings' => [],
    ]);

    $integration->roleMappings()->create([
        'discord_role_id' => 'mapped-role',
        'discord_role_name' => 'Panel Member',
        'local_role_id' => $role->id,
    ]);

    Cache::put('discord-oauth-state:test-state', [
        'integration_id' => $integration->id,
    ], now()->addMinutes(10));

    Http::fake([
        'https://discord.com/api/oauth2/token' => Http::response([
            'access_token' => 'discord-access-token',
            'refresh_token' => 'discord-refresh-token',
            'expires_in' => 3600,
        ]),
        'https://discord.com/api/users/@me' => Http::response([
            'id' => 'discord-user-1',
            'username' => 'discord-user',
            'global_name' => 'Discord User',
            'email' => 'discord@example.com',
            'avatar' => 'avatar-hash',
        ]),
        'https://discord.com/api/users/@me/guilds/123456789/member' => Http::response([
            'roles' => ['mapped-role'],
        ]),
    ]);

    getJson('/api/v1/auth/discord/callback?code=test-code&state=test-state')
        ->assertOk()
        ->assertJsonPath('data.user.email', 'discord@example.com');

    $user = User::query()->where('email', 'discord@example.com')->firstOrFail();

    expect($user->hasRole('discord-member'))->toBeTrue();
    expect(DiscordAccount::query()->where('user_id', $user->id)->exists())->toBeTrue();
});
