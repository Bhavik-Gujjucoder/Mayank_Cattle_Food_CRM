<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Runs all demo bulk seeders in order.
 *
 * Run: php artisan db:seed --class=DemoBulkDataSeeder
 *
 * Optional .env:
 *   BULK_SEED_ORDERS=10000
 *   BULK_SEED_CHUNK=250
 *   BULK_SEED_FORCE=false
 */
class DemoBulkDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoFoundationSeeder::class,
            RawMaterialBulkSeeder::class,
            SalesBulkSeeder::class,
        ]);
    }
}
