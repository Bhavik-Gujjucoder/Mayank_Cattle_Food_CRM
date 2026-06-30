<?php

/**
 * Sales broker: real email login + OTP, then sales module pages.
 */

use Laravel\Dusk\Browser;
use Tests\Browser\Support\DuskModuleHelpers;
use Tests\Browser\Support\ModulePageRegistry;

test('broker email login reaches sales module pages', function () {
    $user = DuskModuleHelpers::brokerUser(['view-order', 'view-dispatch']);

    $pages = array_values(array_filter(
        ModulePageRegistry::salesDefinitions(),
        fn (array $page) => ModulePageRegistry::userCanAccessPage($user, $page)
            && $page['path'] !== '/delivery-pending-payments'
    ));

    expect($pages)->not->toBeEmpty();

    $this->browse(function (Browser $browser) use ($user, $pages) {
        DuskModuleHelpers::loginEmailWithOtp($browser, $user);
        DuskModuleHelpers::smokePages($browser, $pages);
    });
});
