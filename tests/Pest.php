<?php

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(Tests\DuskTestCase::class)
    ->use(DatabaseMigrations::class)
    ->beforeEach(function () {
        Tests\Browser\Support\DuskModuleHelpers::seedPermissions();
    })
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function authUser(array $attrs = []): \App\Models\User
{
    return \App\Models\User::factory()->create(array_merge([
        'status'            => 1,
        'email_verified_at' => now(),
    ], $attrs));
}

function loginEmailStep(\App\Models\User $user, string $password = 'password', bool $remember = false): \Illuminate\Testing\TestResponse
{
    return \Pest\Laravel\post('/login', [
        'email'    => $user->email,
        'password' => $password,
        'remember' => $remember,
    ]);
}

function verifyLoginOtp(\App\Models\User $user, ?string $otp = null): \Illuminate\Testing\TestResponse
{
    $user->refresh();

    return \Pest\Laravel\post(route('verify.otp'), [
        'otp_combined' => $otp ?? (string) $user->otp_code,
    ]);
}

function completeEmailLogin(\App\Models\User $user, string $password = 'password', bool $remember = false): \Illuminate\Testing\TestResponse
{
    loginEmailStep($user, $password, $remember)->assertRedirect(route('verify.otp.form'));

    return verifyLoginOtp($user);
}

function createDealerUser(array $attrs = []): \App\Models\User
{
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'dealer', 'guard_name' => 'web']);

    $user = \App\Models\User::factory()->create(array_merge([
        'phone_no'          => '9876543210',
        'status'            => 1,
        'email_verified_at' => now(),
    ], $attrs));

    $user->assignRole('dealer');

    return $user;
}

function grantPermissions(\App\Models\User $user, array $permissionNames): \App\Models\User
{
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ($permissionNames as $name) {
        \Spatie\Permission\Models\Permission::firstOrCreate(
            ['name' => $name, 'guard_name' => 'web'],
            ['type' => \Database\Seeders\AdminModulePermissionSeeder::typeFor($name) ?? 'test']
        );
    }

    $user->givePermissionTo($permissionNames);

    return $user;
}

function superAdminUser(array $attrs = []): \App\Models\User
{
    $user = authUser($attrs);
    $user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate([
        'name' => 'super admin',
        'guard_name' => 'web',
    ]));

    return $user;
}

function adminUser(array $attrs = []): \App\Models\User
{
    $user = authUser($attrs);
    $user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate([
        'name' => 'admin',
        'guard_name' => 'web',
    ]));

    return $user;
}

/**
 * Fill the six OTP digit inputs on the verify-otp page.
 */
function duskFillOtp(\Laravel\Dusk\Browser $browser, string $otp): void
{
    $code = str_pad(preg_replace('/\D/', '', $otp), 6, '0', STR_PAD_LEFT);
    $jsonCode = json_encode($code);

    $browser->script(<<<JS
        (function () {
            const digits = {$jsonCode}.split('');
            const inputs = document.querySelectorAll('.otp');
            digits.forEach((digit, index) => {
                if (!inputs[index]) {
                    return;
                }
                inputs[index].value = digit;
                inputs[index].dispatchEvent(new Event('input', { bubbles: true }));
            });
            const combined = document.getElementById('otp_combined');
            if (combined) {
                combined.value = digits.join('');
            }
        })();
    JS);
}

/**
 * Submit the verify-otp form (avoids clicking the adjacent Resend button).
 */
function duskSubmitOtp(\Laravel\Dusk\Browser $browser): void
{
    $browser->script('document.getElementById("otpForm").requestSubmit();');
}
