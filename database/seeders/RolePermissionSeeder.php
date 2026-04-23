<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        Permission::create(['name' => 'view servers']);
        Permission::create(['name' => 'create servers']);
        Permission::create(['name' => 'edit servers']);
        Permission::create(['name' => 'delete servers']);
        Permission::create(['name' => 'view players']);
        Permission::create(['name' => 'manage players']);
        Permission::create(['name' => 'view moderation actions']);
        Permission::create(['name' => 'create moderation actions']);
        Permission::create(['name' => 'view audit logs']);
        Permission::create(['name' => 'manage users']);
        Permission::create(['name' => 'manage roles']);
        Permission::create(['name' => 'manage permissions']);

        // Create roles and assign existing permissions
        $adminRole = Role::create(['name' => 'admin']);
        $staffRole = Role::create(['name' => 'staff']);
        $userRole = Role::create(['name' => 'user']);

        // Admin gets all permissions
        $adminRole->givePermissionTo(Permission::all());

        // Staff gets specific permissions
        $staffRole->givePermissionTo([
            'view servers',
            'create servers',
            'edit servers',
            'delete servers',
            'view players',
            'manage players',
            'view moderation actions',
            'create moderation actions',
            'view audit logs',
        ]);

        // User gets basic view permissions
        $userRole->givePermissionTo([
            'view servers',
            'view players',
        ]);

        // Create admin user if not exists
        $admin = User::where('email', 'admin@nwl-api.local')->first();
        if (! $admin) {
            $admin = User::create([
                'name' => 'Admin',
                'email' => 'admin@nwl-api.local',
                'password' => bcrypt('password'),
            ]);
            $admin->assignRole('admin');
        }
    }
}
