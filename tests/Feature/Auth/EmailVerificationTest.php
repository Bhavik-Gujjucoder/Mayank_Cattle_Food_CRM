<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('guest is redirected from verify-email page', function () {
    get('/verify-email')->assertRedirect(route('login'));
});

test('guest is redirected from verification notification endpoint', function () {
    post(route('verification.send'))->assertRedirect(route('login'));
});

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    actingAs($user)->get('/verify-email')->assertStatus(200);
});

test('email can be verified', function () {
    $user = User::factory()->unverified()->create();

    \Illuminate\Support\Facades\Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = actingAs($user)->get($verificationUrl);

    \Illuminate\Support\Facades\Event::assertDispatched(\Illuminate\Auth\Events\Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('verification notification can be resent', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect();

    Notification::assertSentTo($user, VerifyEmail::class);
});
