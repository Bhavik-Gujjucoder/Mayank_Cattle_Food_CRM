<?php

/**
 * Admin: module pages allowed by role + seeded permissions render in Chrome.
 */

use Laravel\Dusk\Browser;
use Tests\Browser\Support\DuskModuleHelpers;
use Tests\Browser\Support\ModulePageRegistry;

test('admin can open permitted module index pages without server errors', function () {
    $user = adminUser();
    $pages = ModulePageRegistry::forUser($user);

    expect($pages)->not->toBeEmpty();

    $this->browse(function (Browser $browser) use ($user, $pages) {
        $browser->loginAs($user);
        DuskModuleHelpers::smokePages($browser, $pages);
    });
});

test('admin cannot open super-admin-only system backup', function () {
    $user = adminUser();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/system/backup')
            ->assertDontSee('Create Backup')
            ->assertMissing('#create-backup-card');
    });
});
