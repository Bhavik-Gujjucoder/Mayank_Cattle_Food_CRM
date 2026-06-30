<?php

/**
 * Legacy smoke alias — prefer SuperAdminModuleTest / AdminModuleTest for full coverage.
 */

use Laravel\Dusk\Browser;

test('admin can open permissions and roles in the browser', function () {
    $user = adminUser();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/permissions')
            ->waitFor('#permission', 20)
            ->waitForText('Permission', 20)
            ->visit('/roles')
            ->waitFor('#roles', 20)
            ->waitForText('Permissions', 20);
    });
});
