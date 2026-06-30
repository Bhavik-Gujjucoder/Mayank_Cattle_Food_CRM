<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Permissions / roles
        $this->call([
            BrandPermissionSeeder::class,
            SupplierBrokerPermissionSeeder::class,
            DeliveryPendingPaymentsPermissionSeeder::class,
            TruckPermissionSeeder::class,
            RawMaterialPermissionSeeder::class,
            SalesPermissionSeeder::class,
            AdminModulePermissionSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
