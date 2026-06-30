<?php

/**
 * Local-only Dusk test: full email login + OTP in a real browser.
 *
 * Target environment: local (via .env.dusk.local)
 * Run: php artisan serve & php artisan dusk --filter=LoginFlowTest
 */

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\Browser\Support\DuskModuleHelpers;

test('email login and OTP reaches the dashboard', function () {
    $user = User::factory()->create([
        'status'            => 1,
        'email_verified_at' => now(),
        'phone_no'          => '9876501234',
    ]);

    $this->browse(function (Browser $browser) use ($user) {
        $browser->visit('/login')
            ->assertSee('Sign In')
            ->type('email', $user->email)
            ->type('password', 'password')
            ->press('Sign In')
            ->waitFor('#otpForm')
            ->assertSee('Please enter the OTP');

        $otp = DuskModuleHelpers::waitForOtpCode($user, 15);

        duskFillOtp($browser, $otp);

        duskSubmitOtp($browser);

        $browser->waitFor('#sidebar-menu', 15)
            ->assertSee('Dashboard');
    });
});

test('guest is redirected from protected pages to login', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/permissions')
            ->waitForLocation(route('login'), 10)
            ->assertSee('Sign In');
    });
});
