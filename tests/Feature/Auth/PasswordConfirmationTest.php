<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('guest is redirected from confirm password screen', function () {
    get('/confirm-password')->assertRedirect(route('login'));
});

test('confirm password screen can be rendered', function () {
    $user = User::factory()->create();

    actingAs($user)->get('/confirm-password')->assertStatus(200);
});

test('password can be confirmed', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post('/confirm-password', ['password' => 'password'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();
});

test('password is not confirmed with invalid password', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post('/confirm-password', ['password' => 'wrong-password'])
        ->assertSessionHasErrors();
});
