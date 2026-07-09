<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class WeeklyReportPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            ['name' => 'view-weekly-report', 'type' => 'weekly-report', 'guard_name' => 'web'],
            ['name' => 'add-weekly-report', 'type' => 'weekly-report', 'guard_name' => 'web'],
            ['name' => 'edit-weekly-report', 'type' => 'weekly-report', 'guard_name' => 'web'],
            ['name' => 'delete-weekly-report', 'type' => 'weekly-report', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['type' => $permission['type']]
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionNames = collect($permissions)->pluck('name')->all();

        foreach (['admin', 'super admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permissionNames);
            }
        }
    }
}
