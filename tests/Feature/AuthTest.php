<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('Authentication API', function () {

    beforeEach(function () {
        // Create the user role that is assigned during registration
        Role::firstOrCreate(['name' => 'user']);
    });

    describe('POST /api/v1/register', function () {
        it('successfully registers a new user', function () {
            $userData = [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
            ];

            $response = $this->postJson('/api/v1/register', $userData);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
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
                ]);

            expect(User::where('email', 'test@example.com')->exists())->toBeTrue();
            expect(User::where('email', 'test@example.com')->first()->hasRole('user'))->toBeTrue();
        });

        it('validates required fields', function () {
            $response = $this->postJson('/api/v1/register', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email', 'password']);
        });

        it('validates email uniqueness', function () {
            User::factory()->create(['email' => 'existing@example.com']);

            $userData = [
                'name' => 'Test User',
                'email' => 'existing@example.com',
                'password' => 'password123',
            ];

            $response = $this->postJson('/api/v1/register', $userData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('validates password strength', function () {
            $userData = [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => '123', // Too short
            ];

            $response = $this->postJson('/api/v1/register', $userData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });
    });

    describe('POST /api/v1/login', function () {
        beforeEach(function () {
            $this->user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => bcrypt('password123'),
            ]);
        });

        it('successfully logs in a user with correct credentials', function () {
            $loginData = [
                'email' => 'test@example.com',
                'password' => 'password123',
            ];

            $response = $this->postJson('/api/v1/login', $loginData);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
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

        it('fails with incorrect credentials', function () {
            $loginData = [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ];

            $response = $this->postJson('/api/v1/login', $loginData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('validates required fields', function () {
            $response = $this->postJson('/api/v1/login', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'password']);
        });
    });

    describe('POST /api/v1/logout', function () {
        it('successfully logs out an authenticated user', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test-token')->plainTextToken;

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->postJson('/api/v1/logout');

            $response->assertStatus(200)
                ->assertJson([
                    'data' => null,
                    'meta' => [
                        'message' => 'Successfully logged out.',
                    ],
                ]);

            expect($user->tokens()->count())->toBe(0);
        });

        it('fails without authentication', function () {
            $response = $this->postJson('/api/v1/logout');

            $response->assertStatus(401);
        });
    });

    describe('GET /api/v1/me', function () {
        it('returns authenticated user information', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test-token')->plainTextToken;

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson('/api/v1/me');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'email',
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
                ]);
        });

        it('fails without authentication', function () {
            $response = $this->getJson('/api/v1/me');

            $response->assertStatus(401);
        });
    });

    describe('POST /api/v1/refresh', function () {
        it('successfully refreshes token', function () {
            $user = User::factory()->create();
            $oldToken = $user->createToken('test-token')->plainTextToken;

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $oldToken,
            ])->postJson('/api/v1/refresh');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'token',
                    ],
                    'meta' => [
                        'token_type',
                    ],
                ]);

            // Verify old token is deleted
            expect($user->tokens()->count())->toBe(1);
        });

        it('fails without authentication', function () {
            $response = $this->postJson('/api/v1/refresh');

            $response->assertStatus(401);
        });
    });
});
