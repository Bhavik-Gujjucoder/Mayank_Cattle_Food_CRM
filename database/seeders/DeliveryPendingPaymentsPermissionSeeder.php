<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DeliveryPendingPaymentsPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permission = 'view-dispatch-pending-payments';

        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);

        foreach (['admin', 'super admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }
}
