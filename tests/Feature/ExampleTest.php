<?php

use function Pest\Laravel\get;

it('redirects guests from the home page to login', function () {
    $response = get('/');

    $response->assertRedirect(route('login'));
});
