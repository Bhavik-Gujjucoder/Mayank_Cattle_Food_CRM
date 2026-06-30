<?php

use Database\Seeders\AdminModulePermissionSeeder;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AdminModulePermissionSeeder::definitions() as $definition) {
            Permission::query()
                ->where('name', $definition['name'])
                ->where('guard_name', 'web')
                ->update(['type' => $definition['type']]);
        }

        Permission::query()
            ->whereIn('type', ['e2e', 'dusk'])
            ->where('guard_name', 'web')
            ->each(function (Permission $permission): void {
                $permission->update([
                    'type' => AdminModulePermissionSeeder::typeFor($permission->name),
                ]);
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Non-destructive data migration — no rollback.
    }
};
