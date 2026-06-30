<?php

/**
 * Staff: only pages granted by explicit permissions (sales profile).
 */

use Laravel\Dusk\Browser;
use Tests\Browser\Support\DuskModuleHelpers;
use Tests\Browser\Support\ModulePageRegistry;

test('staff with sales permissions can open sales module pages', function () {
    $user = DuskModuleHelpers::staffUser([
        'view-order',
        'view-dispatch',
        'view-dispatch-pending-payments',
    ]);

    $pages = array_values(array_filter(
        ModulePageRegistry::forUser($user),
        fn (array $page) => str_starts_with($page['path'], '/order')
            || str_starts_with($page['path'], '/dispatch')
            || str_starts_with($page['path'], '/delivery-pending-payments')
            || $page['path'] === '/'
    ));

    expect($pages)->not->toBeEmpty();

    $this->browse(function (Browser $browser) use ($user, $pages) {
        $browser->loginAs($user);
        DuskModuleHelpers::smokePages($browser, $pages);
    });
});

test('staff without admin role cannot open permissions management', function () {
    $user = DuskModuleHelpers::staffUser(['view-order']);

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/permissions')
            ->assertDontSee('Add Permission')
            ->assertMissing('#permission');
    });
});
