<?php

/**
 * Super admin: all sidebar module index pages render in Chrome.
 */

use Laravel\Dusk\Browser;
use Tests\Browser\Support\DuskModuleHelpers;
use Tests\Browser\Support\ModulePageRegistry;

test('super admin can open every module index without server errors', function () {
    $user = superAdminUser();
    $pages = ModulePageRegistry::forUser($user);

    expect($pages)->not->toBeEmpty();

    $this->browse(function (Browser $browser) use ($user, $pages) {
        $browser->loginAs($user);
        DuskModuleHelpers::smokePages($browser, $pages);
    });
});
