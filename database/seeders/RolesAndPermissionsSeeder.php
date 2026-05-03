<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'plans.manage',
            'users.manage',
            'categories.manage',
            'memories.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findOrCreate('admin')->syncPermissions($permissions);

        Role::findOrCreate('user')->syncPermissions([
            'categories.manage',
            'memories.manage',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
