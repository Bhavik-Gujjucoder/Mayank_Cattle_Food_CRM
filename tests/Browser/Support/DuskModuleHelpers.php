<?php

namespace Tests\Browser\Support;

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
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DuskModuleHelpers
{
    /** @var list<string> */
    private const DUSK_ROLES = [
        'super admin',
        'admin',
        'staff',
        'broker',
        'dealer',
        'transporter',
    ];

    public static function seedPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::DUSK_ROLES as $roleName) {
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

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public static function staffUser(array $permissions, array $attrs = []): User
    {
        $user = authUser($attrs);
        $user->assignRole(Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']));

        if ($permissions !== []) {
            grantPermissions($user, $permissions);
        }

        return $user;
    }

    public static function brokerUser(array $permissions, array $attrs = []): User
    {
        $user = authUser($attrs);
        $user->assignRole(Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']));

        if ($permissions !== []) {
            grantPermissions($user, $permissions);
        }

        return $user;
    }

    /**
     * @return array{user: User, dealer: DealerManagement}
     */
    public static function dealerWithProfile(array $permissions = ['view-order', 'view-dispatch'], array $attrs = []): array
    {
        $broker = User::factory()->create(['status' => 1, 'email_verified_at' => now()]);
        $broker->assignRole(Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']));

        $phone = '9'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);

        $user = User::factory()->create(array_merge([
            'phone_no'          => $phone,
            'status'            => 1,
            'email_verified_at' => now(),
        ], $attrs));

        $user->assignRole(Role::firstOrCreate(['name' => 'dealer', 'guard_name' => 'web']));

        if ($permissions !== []) {
            grantPermissions($user, $permissions);
        }

        $brandId = \App\Models\BrandManagement::query()->value('id')
            ?? \App\Models\BrandManagement::create(['name' => 'Dusk Brand', 'status' => 1])->id;

        // applicant_name, mobile_no, pancard required for migration rollback on SQLite (NOT NULL in schema history).
        $dealer = DealerManagement::create([
            'broker_id'         => $broker->id,
            'brand_id'          => $brandId,
            'user_id'           => $user->id,
            'code_no'           => 'D-'.uniqid(),
            'applicant_name'    => 'Dusk Dealer',
            'firm_shop_name'    => 'Dusk Dealer Firm',
            'firm_shop_address' => 'Dusk test address',
            'mobile_no'         => $phone,
            'pancard'           => 'ABCDE1234F',
        ]);

        return compact('user', 'dealer');
    }

    /**
     * @param  list<array{path: string, selector: string, text: string}>  $pages
     */
    public static function smokePages(Browser $browser, array $pages, int $timeout = 20): void
    {
        foreach ($pages as $page) {
            $browser->visit($page['path'])
                ->assertDontSee('Welcome to XAMPP for Windows')
                ->assertDontSee('500 | SERVER ERROR')
                ->assertDontSee('Server Error')
                ->waitFor($page['selector'], $timeout)
                ->waitForText($page['text'], $timeout)
                ->assertDontSee('Whoops');
        }
    }

    public static function loginDealerByPhone(Browser $browser, User $user, string $password = 'password'): Browser
    {
        return $browser->visit('/login')
            ->assertSee('Sign In')
            ->type('email', (string) $user->phone_no)
            ->type('password', $password)
            ->press('Sign In')
            ->waitFor('#sidebar-menu', 25);
    }

    public static function waitForOtpCode(User $user, int $seconds = 15): string
    {
        $deadline = microtime(true) + $seconds;

        do {
            $otp = $user->fresh()->otp_code;

            if ($otp) {
                return (string) $otp;
            }

            usleep(200_000);
        } while (microtime(true) < $deadline);

        throw new \RuntimeException('OTP was not generated for user #'.$user->id);
    }

    public static function loginEmailWithOtp(Browser $browser, User $user, string $password = 'password'): Browser
    {
        $browser->visit('/login')
            ->assertSee('Sign In')
            ->type('email', $user->email)
            ->type('password', $password)
            ->press('Sign In')
            ->waitFor('#otpForm', 20)
            ->assertSee('Please enter the OTP');

        $otp = self::waitForOtpCode($user);

        duskFillOtp($browser, $otp);
        duskSubmitOtp($browser);

        return $browser->waitFor('#sidebar-menu', 25)
            ->assertDontSee('Invalid or expired OTP');
    }
}
