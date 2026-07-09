<?php

namespace Database\Seeders;

use App\Models\BrandManagement;
use App\Models\DealerManagement;
use App\Models\User;
use Database\Seeders\AdminModulePermissionSeeder;
use Database\Seeders\BrandPermissionSeeder;
use Database\Seeders\DeliveryPendingPaymentsPermissionSeeder;
use Database\Seeders\RawMaterialPermissionSeeder;
use Database\Seeders\SalesPermissionSeeder;
use Database\Seeders\SupplierBrokerPermissionSeeder;
use Database\Seeders\TruckPermissionSeeder;
use Database\Seeders\WeeklyReportPermissionSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Fixed users for Playwright local E2E. Safe to re-run (updateOrCreate).
 *
 * Run: php artisan db:seed --class=E2eTestUserSeeder
 */
class E2eTestUserSeeder extends Seeder
{
    public const SUPER_ADMIN_EMAIL = 'e2e-superadmin@mayank.local';

    public const ADMIN_EMAIL = 'e2e-admin@mayank.local';

    public const STAFF_EMAIL = 'e2e-staff@mayank.local';

    public const BROKER_EMAIL = 'e2e-broker@mayank.local';

    public const DEALER_PHONE = '9876598765';

    public const PASSWORD = 'password';

    /** @var list<string> */
    private const ROLES = [
        'super admin',
        'admin',
        'staff',
        'broker',
        'dealer',
        'transporter',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLES as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        foreach ([
            BrandPermissionSeeder::class,
            SupplierBrokerPermissionSeeder::class,
            DeliveryPendingPaymentsPermissionSeeder::class,
            TruckPermissionSeeder::class,
            RawMaterialPermissionSeeder::class,
            SalesPermissionSeeder::class,
            WeeklyReportPermissionSeeder::class,
            AdminModulePermissionSeeder::class,
        ] as $seederClass) {
            (new $seederClass)->run();
        }

        $this->upsertUser(self::SUPER_ADMIN_EMAIL, 'super admin');
        $this->upsertUser(self::ADMIN_EMAIL, 'admin');

        $staff = $this->upsertUser(self::STAFF_EMAIL, 'staff');
        $staff->syncPermissions([
            'view-order',
            'view-dispatch',
            'view-dispatch-pending-payments',
        ]);

        $broker = $this->upsertUser(self::BROKER_EMAIL, 'broker');
        $broker->syncPermissions(['view-order', 'view-dispatch']);

        $this->seedDealer();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function upsertUser(string $email, string $role): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => 'E2E '.ucfirst($role),
                'password'          => Hash::make(self::PASSWORD),
                'status'            => 1,
                'email_verified_at' => now(),
                'phone_no'          => null,
            ]
        );

        $user->syncRoles([$role]);

        return $user;
    }

    private function seedDealer(): void
    {
        $broker = User::updateOrCreate(
            ['email' => 'e2e-dealer-broker@mayank.local'],
            [
                'name'              => 'E2E Dealer Broker',
                'password'          => Hash::make(self::PASSWORD),
                'status'            => 1,
                'email_verified_at' => now(),
            ]
        );
        $broker->syncRoles(['broker']);

        $dealerUser = User::updateOrCreate(
            ['phone_no' => self::DEALER_PHONE],
            [
                'name'              => 'E2E Dealer',
                'email'             => 'e2e-dealer@mayank.local',
                'password'          => Hash::make(self::PASSWORD),
                'status'            => 1,
                'email_verified_at' => now(),
            ]
        );
        $dealerUser->syncRoles(['dealer']);
        $dealerUser->syncPermissions(['view-order', 'view-dispatch']);

        $brandId = BrandManagement::query()->value('id')
            ?? BrandManagement::create(['name' => 'E2E Brand', 'status' => 1])->id;

        DealerManagement::updateOrCreate(
            ['user_id' => $dealerUser->id],
            [
                'broker_id'         => $broker->id,
                'brand_id'          => $brandId,
                'code_no'           => 'E2E-D001',
                'firm_shop_name'    => 'E2E Dealer Firm',
                'firm_shop_address' => 'E2E test address',
            ]
        );
    }
}
