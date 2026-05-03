<?php

namespace Database\Seeders;

use App\Enums\UserRole;
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

        $guard = 'web';

        $permissionNames = [
            'plans.manage',
            'users.manage',
            'categories.manage',
            'memories.manage',
        ];

        $permissions = collect($permissionNames)
            ->map(fn (string $permission) => Permission::findOrCreate($permission, $guard));

        Role::findOrCreate(UserRole::ADMIN->value, $guard)->syncPermissions($permissions);

        Role::findOrCreate(UserRole::USER->value, $guard)->syncPermissions(
            $permissions->whereIn('name', [
                'categories.manage',
                'memories.manage',
            ]),
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
