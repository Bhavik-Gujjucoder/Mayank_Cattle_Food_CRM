<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;

class RawMaterialPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect([
            // Inventory (materials)
            [
                'name' => 'view-raw-material-inventory',
                'type' => 'raw-material-inventory',
                'guard_name' => 'web',
            ],
            [
                'name' => 'add-raw-material-inventory',
                'type' => 'raw-material-inventory',
                'guard_name' => 'web',  
            ],
            [
                'name' => 'edit-raw-material-inventory',
                'type' => 'raw-material-inventory',
                'guard_name' => 'web',
            ],
            [
                'name' => 'delete-raw-material-inventory',
                'type' => 'raw-material-inventory',
                'guard_name' => 'web',
            ],
            [
                'name' => 'export-raw-material-inventory',
                'type' => 'raw-material-inventory',
                'guard_name' => 'web',
            ],

            // Orders (CRUD permissions already used across Orders/Received)
            [
                'name' => 'view-raw-material-purchas-order',
                'type' => 'raw-material-purchas-order',
                'guard_name' => 'web',
            ],
            [
                'name' => 'add-raw-material-purchas-order',
                'type' => 'raw-material-purchas-order',
                'guard_name' => 'web',
            ],
            [
                'name' => 'edit-raw-material-purchas-order',
                'type' => 'raw-material-purchas-order',
                'guard_name' => 'web',
            ],
            [
                'name' => 'delete-raw-material-purchas-order',
                'type' => 'raw-material-purchas-order',
                'guard_name' => 'web',
            ],
            [
                'name' => 'export-raw-material-purchas-order',
                'type' => 'raw-material-purchas-order',
                'guard_name' => 'web',
            ],

            // Received
            [
                'name' => 'view-raw-material-receive',
                'type' => 'raw-material-receive',
                'guard_name' => 'web',
            ],
            [
                'name' => 'add-raw-material-receive',
                'type' => 'raw-material-receive',
                'guard_name' => 'web',
            ],
            [
                'name' => 'edit-raw-material-receive',
                'type' => 'raw-material-receive',
                'guard_name' => 'web',
            ],
            [
                'name' => 'delete-raw-material-receive',
                'type' => 'raw-material-receive',
                'guard_name' => 'web',
            ],
            [
                'name' => 'export-raw-material-receive',
                'type' => 'raw-material-receive',
                'guard_name' => 'web',
            ],

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
