<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\postJson;

use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('assigns the default role when an authenticated user logs in without roles', function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

    $user = User::factory()->create([
        'email' => 'roleless@example.com',
        'password' => bcrypt('password123'),
    ]);

    expect($user->roles)->toHaveCount(0);

    postJson('/api/v1/login', [
        'email' => 'roleless@example.com',
        'password' => 'password123',
    ])->assertOk();

    expect($user->fresh()->hasRole('user'))->toBeTrue();
});
