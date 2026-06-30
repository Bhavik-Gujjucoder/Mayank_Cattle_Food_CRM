<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Admin-facing module permissions grouped by real module type (not test-only types).
 */
class AdminModulePermissionSeeder extends Seeder
{
    /** @var list<array{name: string, type: string}>|null */
    private static ?array $definitions = null;

    /** @return list<array{name: string, type: string}> */
    public static function definitions(): array
    {
        if (self::$definitions !== null) {
            return self::$definitions;
        }

        self::$definitions = [
            ['name' => 'add-product', 'type' => 'production'],
            ['name' => 'edit-product', 'type' => 'production'],
            ['name' => 'delete-product', 'type' => 'production'],
            ['name' => 'add-oil', 'type' => 'oil'],
            ['name' => 'edit-oil', 'type' => 'oil'],
            ['name' => 'delete-oil', 'type' => 'oil'],
            ['name' => 'add-machine', 'type' => 'machinery'],
            ['name' => 'edit-machine', 'type' => 'machinery'],
            ['name' => 'delete-machine', 'type' => 'machinery'],
            ['name' => 'add-supplier', 'type' => 'supplier'],
            ['name' => 'edit-supplier', 'type' => 'supplier'],
            ['name' => 'delete-supplier', 'type' => 'supplier'],
            ['name' => 'add-broker', 'type' => 'sales-broker'],
            ['name' => 'edit-broker', 'type' => 'sales-broker'],
            ['name' => 'delete-broker', 'type' => 'sales-broker'],
            ['name' => 'add-dealer', 'type' => 'dealer'],
            ['name' => 'edit-dealer', 'type' => 'dealer'],
            ['name' => 'delete-dealer', 'type' => 'dealer'],
            ['name' => 'add-transporter', 'type' => 'transporter'],
            ['name' => 'edit-transporter', 'type' => 'transporter'],
            ['name' => 'delete-transporter', 'type' => 'transporter'],
            ['name' => 'add-user', 'type' => 'admin-staff'],
            ['name' => 'edit-user', 'type' => 'admin-staff'],
            ['name' => 'delete-user', 'type' => 'admin-staff'],
            ['name' => 'add-state', 'type' => 'state'],
            ['name' => 'edit-state', 'type' => 'state'],
            ['name' => 'delete-state', 'type' => 'state'],
            ['name' => 'add-city', 'type' => 'city'],
            ['name' => 'edit-city', 'type' => 'city'],
            ['name' => 'delete-city', 'type' => 'city'],
        ];

        return self::$definitions;
    }

    public static function typeFor(string $name): ?string
    {
        foreach (self::definitions() as $definition) {
            if ($definition['name'] === $name) {
                return $definition['type'];
            }
        }

        return null;
    }

    /** @return list<string> */
    public static function permissionNames(): array
    {
        return collect(self::definitions())->pluck('name')->all();
    }

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::definitions() as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['type' => $permission['type']]
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole) {
            $adminRole->givePermissionTo(self::permissionNames());
        }
    }
}
