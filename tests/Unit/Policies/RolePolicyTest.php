<?php

use App\Models\User;
use App\Policies\Domain\Auth\RolePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('RolePolicy', function () {
    beforeEach(function () {
        $this->policy = new RolePolicy;

        // Create permissions used in the policy
        Permission::create(['name' => 'view roles']);
        Permission::create(['name' => 'create roles']);
        Permission::create(['name' => 'edit roles']);
        Permission::create(['name' => 'delete roles']);
    });

    describe('viewAny()', function () {
        it('allows user with view roles permission', function () {
            $user = User::factory()->create();
            $user->givePermissionTo('view roles');

            $result = $this->policy->viewAny($user);

            expect($result)->toBeTrue();
        });

        it('denies user without view roles permission', function () {
            $user = User::factory()->create();

            $result = $this->policy->viewAny($user);

            expect($result)->toBeFalse();
        });
    });

    describe('view()', function () {
        it('allows user with view roles permission', function () {
            $user = User::factory()->create();
            $role = Role::create(['name' => 'test-role']);
            $user->givePermissionTo('view roles');

            $result = $this->policy->view($user, $role);

            expect($result)->toBeTrue();
        });

        it('denies user without view roles permission', function () {
            $user = User::factory()->create();
            $role = Role::create(['name' => 'test-role']);

            $result = $this->policy->view($user, $role);

            expect($result)->toBeFalse();
        });
    });

    describe('create()', function () {
        it('allows user with create roles permission', function () {
            $user = User::factory()->create();
            $user->givePermissionTo('create roles');

            $result = $this->policy->create($user);

            expect($result)->toBeTrue();
        });

        it('denies user without create roles permission', function () {
            $user = User::factory()->create();

            $result = $this->policy->create($user);

            expect($result)->toBeFalse();
        });
    });

    describe('update()', function () {
        it('allows user with edit roles permission', function () {
            $user = User::factory()->create();
            $role = Role::create(['name' => 'test-role']);
            $user->givePermissionTo('edit roles');

            $result = $this->policy->update($user, $role);

            expect($result)->toBeTrue();
        });

        it('denies user without edit roles permission', function () {
            $user = User::factory()->create();
            $role = Role::create(['name' => 'test-role']);

            $result = $this->policy->update($user, $role);

            expect($result)->toBeFalse();
        });
    });

    describe('delete()', function () {
        it('allows user with delete roles permission', function () {
            $user = User::factory()->create();
            $role = Role::create(['name' => 'test-role']);
            $user->givePermissionTo('delete roles');

            $result = $this->policy->delete($user, $role);

            expect($result)->toBeTrue();
        });

        it('denies user without delete roles permission', function () {
            $user = User::factory()->create();
            $role = Role::create(['name' => 'test-role']);

            $result = $this->policy->delete($user, $role);

            expect($result)->toBeFalse();
        });
    });
});
