<?php

use App\Domain\Tenancy\Services\TenancyService;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Notification::fake();
});

it('logs tenant creation and tenant switching actions', function (): void {
    $user = User::factory()->create();
    app(TenancyService::class)->ensurePersonalTenant($user);
    $token = $user->createToken('tenant-audit')->plainTextToken;

    postJson('/api/v1/tenants', [
        'name' => 'Audit Workspace',
    ], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertCreated();

    $tenant = Tenant::query()->where('name', 'Audit Workspace')->firstOrFail();

    expect(Activity::query()->where('event', 'tenant.created')->exists())->toBeTrue();
    expect(Activity::query()->where('event', 'tenant.created')->latest('id')->first()?->properties['tenant_id'])->toBe($tenant->id);

    postJson('/api/v1/tenants/' . $tenant->id . '/switch', [], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertOk();

    expect(Activity::query()->where('event', 'tenant.switched')->exists())->toBeTrue();
});

it('logs tenant member lifecycle actions', function (): void {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $service = app(TenancyService::class);
    $tenant = $service->ensurePersonalTenant($owner);
    $token = $owner->createToken('tenant-audit')->plainTextToken;

    postJson('/api/v1/tenants/current/members', [
        'email' => $member->email,
        'role' => 'support',
    ], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertCreated();

    expect(Activity::query()->where('event', 'tenant.member_added')->latest('id')->first()?->properties['member_id'])->toBe($member->id);

    patchJson('/api/v1/tenants/current/members/' . $member->id, [
        'role' => 'admin',
    ], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertOk();

    expect(Activity::query()->where('event', 'tenant.member_role_updated')->latest('id')->first()?->properties['new_role'])->toBe('admin');

    deleteJson('/api/v1/tenants/current/members/' . $member->id, [], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertOk();

    expect(Activity::query()->where('event', 'tenant.member_removed')->latest('id')->first()?->properties['member_id'])->toBe($member->id);
    expect($tenant->id)->not->toBeNull();
});

it('logs tenant invitation lifecycle actions', function (): void {
    $owner = User::factory()->create();
    $invitee = User::factory()->create([
        'email' => 'invitee@example.com',
    ]);
    $service = app(TenancyService::class);
    $service->ensurePersonalTenant($owner);
    $token = $owner->createToken('tenant-audit')->plainTextToken;

    postJson('/api/v1/tenants/current/invitations', [
        'email' => $invitee->email,
        'role' => 'support',
        'expires_in_hours' => 24,
    ], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertCreated();

    $invitation = TenantInvitation::query()->where('email', $invitee->email)->firstOrFail();

    expect(Activity::query()->where('event', 'tenant.invitation_created')->latest('id')->first()?->properties['tenant_invitation_id'])->toBe($invitation->id);

    postJson('/api/v1/tenants/current/invitations/' . $invitation->id . '/resend', [], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertOk();

    expect(Activity::query()->where('event', 'tenant.invitation_resent')->exists())->toBeTrue();

    Sanctum::actingAs($invitee);

    postJson('/api/v1/tenants/invitations/' . $invitation->token . '/accept')->assertOk();

    expect(Activity::query()->where('event', 'tenant.invitation_accepted')->latest('id')->first()?->properties['accepted_by_user_id'])->toBe($invitee->id);
});

it('logs invited user registration as a dedicated audit event', function (): void {
    $owner = User::factory()->create();
    $service = app(TenancyService::class);
    $service->ensurePersonalTenant($owner);
    $invitation = $service->createInvitation($owner, 'new-invitee@example.com', 'support', 24);

    postJson('/api/v1/tenants/invitations/' . $invitation->token . '/register', [
        'name' => 'New Invitee',
        'password' => 'password123',
    ])->assertCreated();

    $registeredUser = User::query()->where('email', 'new-invitee@example.com')->firstOrFail();

    expect(Activity::query()->where('event', 'tenant.invitation_registered')->latest('id')->first()?->properties['registered_user_id'])->toBe($registeredUser->id);
});

it('lists audit logs for the current tenant', function (): void {
    $owner = User::factory()->create();
    $service = app(TenancyService::class);
    $service->ensurePersonalTenant($owner);
    $token = $owner->createToken('tenant-audit')->plainTextToken;

    postJson('/api/v1/tenants/current/invitations', [
        'email' => 'audit-invitee@example.com',
        'role' => 'support',
        'expires_in_hours' => 24,
    ], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertCreated();

    getJson('/api/v1/tenants/current/audit-logs', [
        'Authorization' => 'Bearer ' . $token,
    ])
        ->assertOk()
        ->assertJsonPath('data.0.event', 'tenant.invitation_created')
        ->assertJsonPath('data.0.causer.email', $owner->email)
        ->assertJsonPath('data.0.properties.email', 'audit-invitee@example.com')
        ->assertJsonPath('meta.limit', 50);
});

it('filters audit logs by event', function (): void {
    $owner = User::factory()->create();
    $service = app(TenancyService::class);
    $service->ensurePersonalTenant($owner);
    $token = $owner->createToken('tenant-audit')->plainTextToken;

    postJson('/api/v1/tenants/current/invitations', [
        'email' => 'filtered-invitee@example.com',
        'role' => 'support',
        'expires_in_hours' => 24,
    ], [
        'Authorization' => 'Bearer ' . $token,
    ])->assertCreated();

    getJson('/api/v1/tenants/current/audit-logs?event=tenant.member_removed', [
        'Authorization' => 'Bearer ' . $token,
    ])
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('forbids audit logs without the tenant permission', function (): void {
    $owner = User::factory()->create();
    $support = User::factory()->create();
    $service = app(TenancyService::class);
    $tenant = $service->ensurePersonalTenant($owner);
    $service->assignUserToTenant($tenant, $support, 'support');
    $service->switchTenant($support, $tenant);
    $token = $support->createToken('tenant-audit')->plainTextToken;

    getJson('/api/v1/tenants/current/audit-logs', [
        'Authorization' => 'Bearer ' . $token,
    ])->assertForbidden();
});
