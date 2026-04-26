<?php

use App\Domain\Tenancy\Services\TenancyService;
use App\Models\DiscordIntegration;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Notification::fake();
});

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

it('lists the current tenant members with their tenant roles', function (): void {
    $owner = User::factory()->create([
        'name' => 'Alpha Owner',
    ]);
    $member = User::factory()->create([
        'name' => 'Zulu Support',
    ]);
    $service = app(TenancyService::class);
    $tenant = $service->ensurePersonalTenant($owner);
    $service->assignUserToTenant($tenant, $member, 'support');
    $token = $owner->createToken('tenant-members')->plainTextToken;

    getJson('/api/v1/tenants/current/members', [
        'Authorization' => 'Bearer ' . $token,
    ])->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.membership_role', 'owner')
        ->assertJsonPath('data.0.is_current_user', true)
        ->assertJsonPath('data.1.membership_role', 'support')
        ->assertJsonPath('data.1.email', $member->email)
        ->assertJsonPath('data.1.is_current_user', false);
});

it('adds an existing user to the current tenant', function (): void {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $service = app(TenancyService::class);
    $service->ensurePersonalTenant($owner);
    $token = $owner->createToken('tenant-add')->plainTextToken;

    postJson('/api/v1/tenants/current/members', [
        'email' => $member->email,
        'role' => 'support',
    ], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertCreated()
        ->assertJsonPath('data.email', $member->email)
        ->assertJsonPath('data.membership_role', 'support')
        ->assertJsonPath('meta.message', 'Tenant member added successfully.');

    expect($owner->currentTenantOrFail()->users()->whereKey($member->id)->exists())->toBeTrue();
});

it('updates a tenant member role', function (): void {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $service = app(TenancyService::class);
    $tenant = $service->ensurePersonalTenant($owner);
    $service->assignUserToTenant($tenant, $member, 'support');
    $token = $owner->createToken('tenant-update')->plainTextToken;

    patchJson('/api/v1/tenants/current/members/' . $member->id, [
        'role' => 'admin',
    ], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertOk()
        ->assertJsonPath('data.membership_role', 'admin')
        ->assertJsonPath('meta.message', 'Tenant member role updated successfully.');

    expect($member->fresh()->tenantMembershipRole($tenant))->toBe('admin');
});

it('forbids adding a tenant member without tenant permissions', function (): void {
    $owner = User::factory()->create();
    $support = User::factory()->create();
    $target = User::factory()->create();
    $service = app(TenancyService::class);
    $tenant = $service->ensurePersonalTenant($owner);
    $service->assignUserToTenant($tenant, $support, 'support');
    $service->switchTenant($support, $tenant);
    $token = $support->createToken('tenant-add')->plainTextToken;

    postJson('/api/v1/tenants/current/members', [
        'email' => $target->email,
        'role' => 'member',
    ], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertForbidden()
        ->assertJson([
            'message' => 'Forbidden',
            'code' => 'authorization_denied',
            'errors' => [],
        ]);
});

it('prevents removing the current tenant owner', function (): void {
    $owner = User::factory()->create();
    $service = app(TenancyService::class);
    $tenant = $service->ensurePersonalTenant($owner);
    $token = $owner->createToken('tenant-remove')->plainTextToken;

    deleteJson('/api/v1/tenants/current/members/' . $owner->id, [], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['user']);
});

it('creates and lists tenant invitations', function (): void {
    $owner = User::factory()->create();
    $service = app(TenancyService::class);
    $service->ensurePersonalTenant($owner);
    $token = $owner->createToken('tenant-invite')->plainTextToken;

    postJson('/api/v1/tenants/current/invitations', [
        'email' => 'invitee@example.com',
        'role' => 'support',
        'expires_in_hours' => 24,
    ], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertCreated()
        ->assertJsonPath('data.email', 'invitee@example.com')
        ->assertJsonPath('data.role', 'support')
        ->assertJsonPath('data.is_pending', true)
        ->assertJsonPath('data.send_count', 1)
        ->assertJsonPath('data.last_sent_at', fn (?string $value): bool => is_string($value) && $value !== '')
        ->assertJsonPath('meta.message', 'Tenant invitation created successfully.');

    getJson('/api/v1/tenants/current/invitations', [
        'Authorization' => 'Bearer ' . $token,
    ])->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.email', 'invitee@example.com')
        ->assertJsonPath('data.0.role', 'support');

    Notification::assertCount(1);
});

it('resends a pending tenant invitation', function (): void {
    $owner = User::factory()->create();
    $service = app(TenancyService::class);
    $service->ensurePersonalTenant($owner);
    $invitation = $service->createInvitation($owner, 'invitee@example.com', 'support', 24);
    $token = $owner->createToken('tenant-resend')->plainTextToken;

    postJson('/api/v1/tenants/current/invitations/' . $invitation->id . '/resend', [], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertOk()
        ->assertJsonPath('data.email', 'invitee@example.com')
        ->assertJsonPath('data.send_count', 2)
        ->assertJsonPath('meta.message', 'Tenant invitation resent successfully.');

    expect($invitation->fresh()?->send_count)->toBe(2);
    expect($invitation->fresh()?->last_sent_at)->not->toBeNull();
    Notification::assertCount(2);
});

it('accepts a tenant invitation for the authenticated user', function (): void {
    $owner = User::factory()->create();
    $invitee = User::factory()->create([
        'email' => 'invitee@example.com',
    ]);
    $service = app(TenancyService::class);
    $tenant = $service->ensurePersonalTenant($owner);
    $invitation = $service->createInvitation($owner, $invitee->email, 'support', 24);
    $token = $invitee->createToken('tenant-accept')->plainTextToken;

    postJson('/api/v1/tenants/invitations/' . $invitation->token . '/accept', [], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertOk()
        ->assertJsonPath('data.email', $invitee->email)
        ->assertJsonPath('data.accepted_at', fn (?string $value): bool => is_string($value) && $value !== '')
        ->assertJsonPath('meta.message', 'Tenant invitation accepted successfully.');

    expect($invitee->fresh()->current_tenant_id)->toBe($tenant->id);
    expect($tenant->users()->whereKey($invitee->id)->exists())->toBeTrue();
    expect($invitee->fresh()->tenantMembershipRole($tenant))->toBe('support');
});

it('rejects accepting a tenant invitation for another email address', function (): void {
    $owner = User::factory()->create();
    $wrongUser = User::factory()->create([
        'email' => 'wrong@example.com',
    ]);
    $service = app(TenancyService::class);
    $service->ensurePersonalTenant($owner);
    $invitation = $service->createInvitation($owner, 'invitee@example.com', 'support', 24);
    $token = $wrongUser->createToken('tenant-accept')->plainTextToken;

    postJson('/api/v1/tenants/invitations/' . $invitation->token . '/accept', [], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('revokes a pending tenant invitation', function (): void {
    $owner = User::factory()->create();
    $service = app(TenancyService::class);
    $service->ensurePersonalTenant($owner);
    $invitation = $service->createInvitation($owner, 'invitee@example.com', 'support', 24);
    $token = $owner->createToken('tenant-revoke')->plainTextToken;

    deleteJson('/api/v1/tenants/current/invitations/' . $invitation->id, [], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertOk()
        ->assertJsonPath('meta.message', 'Tenant invitation revoked successfully.');

    expect($invitation->fresh()?->revoked_at)->not->toBeNull();
});

it('prevents accepting a revoked tenant invitation', function (): void {
    $owner = User::factory()->create();
    $invitee = User::factory()->create([
        'email' => 'invitee@example.com',
    ]);
    $service = app(TenancyService::class);
    $service->ensurePersonalTenant($owner);
    $invitation = $service->createInvitation($owner, $invitee->email, 'support', 24);
    $service->revokeInvitation($owner, $invitation);
    $token = $invitee->createToken('tenant-accept')->plainTextToken;

    postJson('/api/v1/tenants/invitations/' . $invitation->token . '/accept', [], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['token']);
});
