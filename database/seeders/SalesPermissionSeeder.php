<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SalesPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect([
            // Orders
            [
                'name' => 'view-order',
                'type' => 'soda-order',
                'guard_name' => 'web',
            ],
            [
                'name' => 'add-order',
                'type' => 'soda-order',
                'guard_name' => 'web',
            ],
            [
                'name' => 'edit-order',
                'type' => 'soda-order',
                'guard_name' => 'web',
            ],
            [
                'name' => 'delete-order',
                'type' => 'soda-order',
                'guard_name' => 'web',
            ],
            [
                'name' => 'view-dispatch',
                'type' => 'dispatch',
                'guard_name' => 'web',
            ],
            [
                'name' => 'add-dispatch',
                'type' => 'dispatch',
                'guard_name' => 'web',
            ],
            [
                'name' => 'edit-dispatch',
                'type' => 'dispatch',
                'guard_name' => 'web',
            ],
            [
                'name' => 'delete-dispatch',
                'type' => 'dispatch',
                'guard_name' => 'web',
            ],
            [
                'name' => 'view-dispatch-pending-payments',
                'type' => 'dispatch',
                'guard_name' => 'web',
            ]
        ])->toArray();

        $permissionNames = collect($permissions)->pluck('name')->values();

        // Remove existing permissions first (and detach from roles/users)
        $existingIds = Permission::query()
            ->whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->pluck('id');

        if ($existingIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $existingIds)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $existingIds)->delete();
            Permission::query()->whereIn('id', $existingIds)->delete();
        }

        // Recreate permissions
        Permission::insert($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Assign to admin role by default (same as other modules)
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissionNames->all());
        }
    }
}

