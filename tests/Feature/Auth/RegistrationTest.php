<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('registration screen can be rendered', function () {
    get('/register')->assertStatus(200);
});

test('authenticated user is redirected away from registration', function () {
    actingAs(User::factory()->create())
        ->get('/register')
        ->assertRedirect();
});

test('new users can register', function () {
    $response = post('/register', [
        'name'                  => 'Test User',
        'email'                 => 'test-register@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registration fails when name is missing', function () {
    post('/register', [
        'email'                 => 'newuser@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors(['name']);

    assertGuest();
});

test('registration fails when email is missing', function () {
    post('/register', [
        'name'                  => 'Test User',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors(['email']);

    assertGuest();
});

test('registration fails with invalid email format', function () {
    post('/register', [
        'name'                  => 'Test User',
        'email'                 => 'not-a-valid-email',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors(['email']);

    assertGuest();
});

test('registration fails when email is already taken', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    post('/register', [
        'name'                  => 'Another User',
        'email'                 => 'taken@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors(['email']);

    assertGuest();
});

test('registration fails when password is missing', function () {
    post('/register', [
        'name'  => 'Test User',
        'email' => 'nopass@example.com',
    ])->assertSessionHasErrors(['password']);

    assertGuest();
});

test('registration fails when password confirmation does not match', function () {
    post('/register', [
        'name'                  => 'Test User',
        'email'                 => 'mismatch@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'differentpassword',
    ])->assertSessionHasErrors(['password']);

    assertGuest();
});
