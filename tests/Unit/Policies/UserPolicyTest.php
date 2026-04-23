<?php

use App\Models\User;
use App\Policies\Domain\Auth\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('UserPolicy', function () {
    beforeEach(function () {
        $this->policy = new UserPolicy;

        // Create permissions used in the policy
        Permission::firstOrCreate(['name' => 'view users']);
        Permission::firstOrCreate(['name' => 'create users']);
        Permission::firstOrCreate(['name' => 'edit users']);
        Permission::firstOrCreate(['name' => 'delete users']);
    });

    describe('viewAny()', function () {
        it('allows user with view users permission', function () {
            $user = User::factory()->create();
            $user->givePermissionTo('view users');

            $result = $this->policy->viewAny($user);

            expect($result)->toBeTrue();
        });

        it('denies user without view users permission', function () {
            $user = User::factory()->create();

            $result = $this->policy->viewAny($user);

            expect($result)->toBeFalse();
        });
    });

    describe('view()', function () {
        it('allows user with view users permission', function () {
            $user = User::factory()->create();
            $targetUser = User::factory()->create();
            $user->givePermissionTo('view users');

            $result = $this->policy->view($user, $targetUser);

            expect($result)->toBeTrue();
        });

        it('allows user to view their own profile', function () {
            $user = User::factory()->create();

            $result = $this->policy->view($user, $user);

            expect($result)->toBeTrue();
        });

        it('denies user without permission viewing another user', function () {
            $user = User::factory()->create();
            $targetUser = User::factory()->create();

            $result = $this->policy->view($user, $targetUser);

            expect($result)->toBeFalse();
        });
    });

    describe('create()', function () {
        it('allows user with create users permission', function () {
            $user = User::factory()->create();
            $user->givePermissionTo('create users');

            $result = $this->policy->create($user);

            expect($result)->toBeTrue();
        });

        it('denies user without create users permission', function () {
            $user = User::factory()->create();

            $result = $this->policy->create($user);

            expect($result)->toBeFalse();
        });
    });

    describe('update()', function () {
        it('allows user with edit users permission', function () {
            $user = User::factory()->create();
            $targetUser = User::factory()->create();
            $user->givePermissionTo('edit users');

            $result = $this->policy->update($user, $targetUser);

            expect($result)->toBeTrue();
        });

        it('allows user to update their own profile', function () {
            $user = User::factory()->create();

            $result = $this->policy->update($user, $user);

            expect($result)->toBeTrue();
        });

        it('denies user without permission updating another user', function () {
            $user = User::factory()->create();
            $targetUser = User::factory()->create();

            $result = $this->policy->update($user, $targetUser);

            expect($result)->toBeFalse();
        });
    });

    describe('delete()', function () {
        it('allows user with delete users permission', function () {
            $user = User::factory()->create();
            $targetUser = User::factory()->create();
            $user->givePermissionTo('delete users');

            $result = $this->policy->delete($user, $targetUser);

            expect($result)->toBeTrue();
        });

        it('denies user without delete users permission', function () {
            $user = User::factory()->create();
            $targetUser = User::factory()->create();

            $result = $this->policy->delete($user, $targetUser);

            expect($result)->toBeFalse();
        });
    });
});
