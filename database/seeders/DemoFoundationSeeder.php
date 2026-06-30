<?php

namespace Database\Seeders;

use App\Models\BrandManagement;
use App\Models\CityManagement;
use App\Models\DealerManagement;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\StateManagement;
use App\Models\Supplier;
use App\Models\SupplierBroker;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Master data for demo / bulk seeders. Safe to re-run (skips when marker records exist).
 *
 * Run: php artisan db:seed --class=DemoFoundationSeeder
 */
class DemoFoundationSeeder extends Seeder
{
    public const MARKER = 'Demo Bulk';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['broker', 'dealer', 'transporter'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $this->seedPaymentSettings();

        if (RawMaterial::where('name', 'like', self::MARKER . ' Material %')->exists()) {
            $this->command?->info('Demo foundation already present — skipping master data.');

            return;
        }

        $this->command?->info('Seeding demo foundation data…');

        $states = $this->seedStates();
        $cities = $this->seedCities($states);
        $categories = $this->seedCategories();
        $materialIds = $this->seedMaterials($categories);
        $brokerIds = $this->seedSupplierBrokers();
        $this->seedSuppliers($cities, $brokerIds);
        $brandIds = $this->seedBrands();
        $this->seedProducts($brandIds);
        $salesBrokers = $this->seedSalesBrokers();
        $this->seedDealers($salesBrokers, $brandIds, $cities, $states);
        $this->seedTransporters();

        $this->command?->info(sprintf(
            'Foundation ready: %d materials, %d suppliers, %d dealers.',
            count($materialIds),
            Supplier::where('name', 'like', self::MARKER . ' Supplier %')->count(),
            DealerManagement::where('code_no', 'like', 'DEMO-D%')->count()
        ));
    }

    private function seedPaymentSettings(): void
    {
        $now = now();

        foreach ([
            'payment_due_days'   => '7',
            'payment_due_amount' => '5',
        ] as $key => $value) {
            DB::table('general_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    /** @return list<int> */
    private function seedStates(): array
    {
        $names = ['Maharashtra', 'Gujarat', 'Karnataka', 'Rajasthan', 'Madhya Pradesh'];
        $ids = [];

        foreach ($names as $name) {
            $ids[] = StateManagement::create([
                'state_name' => self::MARKER . ' ' . $name,
                'status'     => 1,
            ])->id;
        }

        return $ids;
    }

    /**
     * @param  list<int>  $stateIds
     * @return list<int>
     */
    private function seedCities(array $stateIds): array
    {
        $cityNames = [
            'Pune', 'Mumbai', 'Nagpur', 'Ahmedabad', 'Surat',
            'Rajkot', 'Bangalore', 'Hubli', 'Jaipur', 'Udaipur',
            'Indore', 'Bhopal', 'Kolhapur', 'Nashik', 'Belgaum',
            'Gokak', 'Solapur', 'Vadodara', 'Jodhpur', 'Gwalior',
            'Satara', 'Sangli', 'Latur', 'Akola', 'Amravati',
        ];

        $ids = [];

        foreach ($cityNames as $i => $cityName) {
            $ids[] = CityManagement::create([
                'state_id'  => $stateIds[$i % count($stateIds)],
                'city_name' => self::MARKER . ' ' . $cityName,
                'status'    => 1,
            ])->id;
        }

        return $ids;
    }

    /** @return list<int> */
    private function seedCategories(): array
    {
        $names = [
            'Oil Cakes', 'Grains', 'Minerals', 'Fibers', 'Proteins',
            'Additives', 'By-Products', 'Pellets', 'Meals', 'Straws',
            'Molasses', 'Vitamins', 'Binders', 'Premix', 'Specialty',
        ];

        $ids = [];

        foreach ($names as $i => $name) {
            $ids[] = RawMaterialCategory::create([
                'category_unique_id' => 'DEMO-RMC-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'name'               => self::MARKER . ' ' . $name,
                'status'             => 1,
            ])->id;
        }

        return $ids;
    }

    /**
     * @param  list<int>  $categoryIds
     * @return list<int>
     */
    private function seedMaterials(array $categoryIds): array
    {
        $units = ['ton', 'kg'];
        $ids = [];

        for ($i = 1; $i <= 80; $i++) {
            $ids[] = RawMaterial::create([
                'raw_material_unique_id'   => 'DEMO-RM-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'raw_material_category_id' => $categoryIds[($i - 1) % count($categoryIds)],
                'name'                     => self::MARKER . ' Material ' . $i,
                'unit'                     => $units[$i % 2],
                'status'                   => 1,
            ])->id;
        }

        return $ids;
    }

    /** @return list<int> */
    private function seedSupplierBrokers(): array
    {
        $ids = [];

        for ($i = 1; $i <= 20; $i++) {
            $ids[] = SupplierBroker::create([
                'name'   => self::MARKER . ' Supplier Broker ' . $i,
                'status' => 1,
            ])->id;
        }

        return $ids;
    }

    /**
     * @param  list<int>  $cityIds
     * @param  list<int>  $brokerIds
     */
    private function seedSuppliers(array $cityIds, array $brokerIds): void
    {
        for ($i = 1; $i <= 150; $i++) {
            $cityId = $cityIds[($i - 1) % count($cityIds)];

            Supplier::create([
                'name'                => self::MARKER . ' Supplier ' . $i,
                'mobile'              => '98' . str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                'city_id'             => $cityId,
                'supplier_broker_id'  => $brokerIds[($i - 1) % count($brokerIds)],
                'status'              => 1,
            ]);
        }
    }

    /** @return list<int> */
    private function seedBrands(): array
    {
        $ids = [];

        for ($i = 1; $i <= 8; $i++) {
            $ids[] = BrandManagement::create([
                'name'   => self::MARKER . ' Brand ' . $i,
                'status' => 1,
            ])->id;
        }

        return $ids;
    }

    /** @param  list<int>  $brandIds */
    private function seedProducts(array $brandIds): void
    {
        $productNames = [
            'Cottonseed Cake', 'Soybean Meal', 'Wheat Straw', 'Maize Gluten',
            'Rice Bran', 'Groundnut Cake', 'Sunflower Meal', 'Mustard DOC',
            'Cattle Pellet A', 'Cattle Pellet B', 'Mineral Mix', 'Salt Lick Block',
            'Bypass Fat', 'Molasses Blend', 'Alfalfa Hay',
        ];

        foreach ($brandIds as $brandId) {
            foreach ($productNames as $j => $name) {
                Product::create([
                    'name'     => self::MARKER . ' ' . $name . ' B' . $brandId,
                    'brand_id' => $brandId,
                    'unit'     => 'Bag',
                    'price'    => fake()->randomFloat(2, 800, 2500),
                    'status'   => 1,
                ]);
            }
        }
    }

    /** @return list<int> */
    private function seedSalesBrokers(): array
    {
        $role = Role::where('name', 'broker')->first();
        $ids = [];

        for ($i = 1; $i <= 15; $i++) {
            $user = User::create([
                'name'              => self::MARKER . ' Broker ' . $i,
                'email'             => 'demo-broker-' . $i . '@bulk.local',
                'password'          => Hash::make('password'),
                'status'            => 1,
                'email_verified_at' => now(),
            ]);
            $user->assignRole($role);
            $ids[] = $user->id;
        }

        return $ids;
    }

    /**
     * @param  list<int>  $brokerIds
     * @param  list<int>  $brandIds
     * @param  list<int>  $cityIds
     * @param  list<int>  $stateIds
     */
    private function seedDealers(array $brokerIds, array $brandIds, array $cityIds, array $stateIds): void
    {
        $dealerRole = Role::where('name', 'dealer')->first();

        for ($i = 1; $i <= 500; $i++) {
            $dealerUser = User::create([
                'name'              => self::MARKER . ' Dealer ' . $i,
                'email'             => 'demo-dealer-' . $i . '@bulk.local',
                'phone_no'          => '91' . str_pad((string) (1000000 + $i), 8, '0', STR_PAD_LEFT),
                'password'          => Hash::make('password'),
                'status'            => 1,
                'email_verified_at' => now(),
            ]);
            $dealerUser->assignRole($dealerRole);

            DealerManagement::create([
                'broker_id'         => $brokerIds[($i - 1) % count($brokerIds)],
                'brand_id'          => $brandIds[($i - 1) % count($brandIds)],
                'user_id'           => $dealerUser->id,
                'city_id'           => $cityIds[($i - 1) % count($cityIds)],
                'state_id'          => $stateIds[($i - 1) % count($stateIds)],
                'code_no'           => 'DEMO-D' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'firm_shop_name'    => self::MARKER . ' Firm ' . $i,
                'firm_shop_address' => 'Demo address line ' . $i . ', bulk seed',
            ]);
        }
    }

    private function seedTransporters(): void
    {
        $role = Role::where('name', 'transporter')->first();

        for ($i = 1; $i <= 30; $i++) {
            $user = User::create([
                'name'              => self::MARKER . ' Transporter ' . $i,
                'email'             => 'demo-transporter-' . $i . '@bulk.local',
                'password'          => Hash::make('password'),
                'status'            => 1,
                'email_verified_at' => now(),
            ]);
            $user->assignRole($role);
        }
    }
}
