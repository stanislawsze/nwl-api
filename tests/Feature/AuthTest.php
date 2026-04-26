<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user']);
});

it('successfully registers a new user', function (): void {
    $response = postJson('/api/v1/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'current_tenant',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                    'roles',
                    'permissions',
                ],
                'token',
            ],
            'meta' => [
                'token_type',
            ],
        ])
        ->assertJsonPath('data.user.current_tenant.owner_user_id', fn (int $ownerUserId): bool => $ownerUserId > 0);

    $user = User::where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user?->hasRole('user'))->toBeTrue();
    expect($user?->current_tenant_id)->not->toBeNull();
    expect(Tenant::query()->where('owner_user_id', $user?->id)->exists())->toBeTrue();
    $response->assertJsonPath('data.user.current_tenant.owner_user_id', $user?->id);
});

it('validates required fields on registration', function (): void {
    postJson('/api/v1/register', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

it('validates email uniqueness on registration', function (): void {
    User::factory()->create(['email' => 'existing@example.com']);

    postJson('/api/v1/register', [
        'name' => 'Test User',
        'email' => 'existing@example.com',
        'password' => 'password123',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('validates password strength on registration', function (): void {
    postJson('/api/v1/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => '123',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('successfully logs in a user with correct credentials', function (): void {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ])->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'current_tenant',
                    'roles',
                    'permissions',
                ],
                'token',
            ],
            'meta' => [
                'token_type',
            ],
        ]);
});

it('fails login with incorrect credentials', function (): void {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ])->assertStatus(401)
        ->assertJson([
            'message' => 'Unauthenticated.',
            'code' => 'unauthenticated',
            'errors' => [],
        ]);
});

it('validates required fields on login', function (): void {
    postJson('/api/v1/login', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

it('successfully logs out an authenticated user', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    postJson('/api/v1/logout', [], [
        'Authorization' => 'Bearer ' . $token,
    ])
        ->assertStatus(200)
        ->assertJson([
            'data' => null,
            'meta' => [
                'message' => 'Successfully logged out.',
            ],
        ]);

    expect($user->tokens()->count())->toBe(0);
});

it('fails logout without authentication', function (): void {
    postJson('/api/v1/logout')
        ->assertStatus(401);
});

it('returns authenticated user information', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    getJson('/api/v1/me', [
        'Authorization' => 'Bearer ' . $token,
    ])
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'current_tenant',
                'roles',
                'permissions',
            ],
        ])
        ->assertJson([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ])
        ->assertJsonPath('data.current_tenant.id', $user->currentTenantOrFail()->id);
});

it('fails me without authentication', function (): void {
    getJson('/api/v1/me')
        ->assertStatus(401);
});

it('successfully refreshes token', function (): void {
    $user = User::factory()->create();
    $oldToken = $user->createToken('test-token')->plainTextToken;

    postJson('/api/v1/refresh', [], [
        'Authorization' => 'Bearer ' . $oldToken,
    ])
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'token',
            ],
            'meta' => [
                'token_type',
            ],
        ]);

    expect($user->tokens()->count())->toBe(1);
});

it('fails refresh without authentication', function (): void {
    postJson('/api/v1/refresh')
        ->assertStatus(401);
});
