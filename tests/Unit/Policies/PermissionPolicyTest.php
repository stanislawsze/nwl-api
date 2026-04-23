<?php

use App\Models\User;
use App\Policies\Domain\Auth\PermissionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('PermissionPolicy', function () {
    beforeEach(function () {
        $this->policy = new PermissionPolicy;

        // Create permissions used in the policy
        Permission::firstOrCreate(['name' => 'view permissions']);
        Permission::firstOrCreate(['name' => 'create permissions']);
        Permission::firstOrCreate(['name' => 'edit permissions']);
        Permission::firstOrCreate(['name' => 'delete permissions']);
    });

    describe('viewAny()', function () {
        it('allows user with view permissions permission', function () {
            $user = User::factory()->create();
            $user->givePermissionTo('view permissions');

            $result = $this->policy->viewAny($user);

            expect($result)->toBeTrue();
        });

        it('denies user without view permissions permission', function () {
            $user = User::factory()->create();

            $result = $this->policy->viewAny($user);

            expect($result)->toBeFalse();
        });
    });

    describe('view()', function () {
        it('allows user with view permissions permission', function () {
            $user = User::factory()->create();
            $permission = Permission::create(['name' => 'test-permission']);
            $user->givePermissionTo('view permissions');

            $result = $this->policy->view($user, $permission);

            expect($result)->toBeTrue();
        });

        it('denies user without view permissions permission', function () {
            $user = User::factory()->create();
            $permission = Permission::create(['name' => 'test-permission']);

            $result = $this->policy->view($user, $permission);

            expect($result)->toBeFalse();
        });
    });

    describe('create()', function () {
        it('allows user with create permissions permission', function () {
            $user = User::factory()->create();
            $user->givePermissionTo('create permissions');

            $result = $this->policy->create($user);

            expect($result)->toBeTrue();
        });

        it('denies user without create permissions permission', function () {
            $user = User::factory()->create();

            $result = $this->policy->create($user);

            expect($result)->toBeFalse();
        });
    });

    describe('update()', function () {
        it('allows user with edit permissions permission', function () {
            $user = User::factory()->create();
            $permission = Permission::create(['name' => 'test-permission']);
            $user->givePermissionTo('edit permissions');

            $result = $this->policy->update($user, $permission);

            expect($result)->toBeTrue();
        });

        it('denies user without edit permissions permission', function () {
            $user = User::factory()->create();
            $permission = Permission::create(['name' => 'test-permission']);

            $result = $this->policy->update($user, $permission);

            expect($result)->toBeFalse();
        });
    });

    describe('delete()', function () {
        it('allows user with delete permissions permission', function () {
            $user = User::factory()->create();
            $permission = Permission::create(['name' => 'test-permission']);
            $user->givePermissionTo('delete permissions');

            $result = $this->policy->delete($user, $permission);

            expect($result)->toBeTrue();
        });

        it('denies user without delete permissions permission', function () {
            $user = User::factory()->create();
            $permission = Permission::create(['name' => 'test-permission']);

            $result = $this->policy->delete($user, $permission);

            expect($result)->toBeFalse();
        });
    });
});
