<?php

use App\Http\Resources\Auth\AuthResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('AuthResource', function () {
    beforeEach(function () {
        // Create the user role and permission
        Role::firstOrCreate(['name' => 'user']);
        Permission::firstOrCreate(['name' => 'view users']);
    });

    it('transforms user data correctly', function () {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $user->assignRole('user');
        $user->givePermissionTo('view users');

        $resource = new AuthResource($user->load('roles', 'permissions'));
        $result = $resource->toArray(request());

        expect($result)->toHaveKey('id');
        expect($result)->toHaveKey('name');
        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('email_verified_at');
        expect($result)->toHaveKey('created_at');
        expect($result)->toHaveKey('updated_at');
        expect($result)->toHaveKey('roles');
        expect($result)->toHaveKey('permissions');

        expect($result['id'])->toBe($user->id);
        expect($result['name'])->toBe('Test User');
        expect($result['email'])->toBe('test@example.com');
        expect($result['roles'])->toBe(['user']);
        expect($result['permissions'])->toBe(['view users']);
    });

    it('handles user without roles and permissions', function () {
        $user = User::factory()->create();

        $resource = new AuthResource($user->load('roles', 'permissions'));
        $result = $resource->toArray(request());

        expect($result['roles'])->toBe([]);
        expect($result['permissions'])->toBe([]);
    });
});
