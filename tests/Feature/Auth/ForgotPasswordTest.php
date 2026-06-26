<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

// ─────────────────────────────────────────────
//  Forgot password page
// ─────────────────────────────────────────────

describe('forgot password page', function () {
    it('renders the forgot password screen', function () {
        get('/forgot-password')
            ->assertOk()
            ->assertViewIs('auth.forgot-password');
    });

    it('redirects authenticated users away from forgot password', function () {
        actingAs(authUser())->get('/forgot-password')->assertRedirect();
    });
});

// ─────────────────────────────────────────────
//  Password reset link request
// ─────────────────────────────────────────────

describe('password reset link request', function () {
    it('sends reset link for valid registered email', function () {
        Notification::fake();

        $user = authUser();

        post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    });

    it('rejects request with invalid email format', function () {
        Notification::fake();

        post('/forgot-password', ['email' => 'not-an-email'])
            ->assertSessionHasErrors(['email']);

        Notification::assertNothingSent();
    });

    it('rejects request with empty email', function () {
        Notification::fake();

        post('/forgot-password', ['email' => ''])
            ->assertSessionHasErrors(['email']);

        Notification::assertNothingSent();
    });

    it('does not send notification for unregistered email', function () {
        Notification::fake();

        post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertSessionHasErrors(['email']);

        Notification::assertNothingSent();
    });
});

// ─────────────────────────────────────────────
//  Reset password screen
// ─────────────────────────────────────────────

describe('reset password screen', function () {
    it('renders reset password form with valid token', function () {
        Notification::fake();

        $user = authUser();

        post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            get('/reset-password/' . $notification->token)->assertOk();

            return true;
        });
    });
});

// ─────────────────────────────────────────────
//  Password reset — success
// ─────────────────────────────────────────────

describe('password reset success', function () {
    it('resets password with valid token', function () {
        Notification::fake();

        $user = authUser();

        post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            post('/reset-password', [
                'token'                 => $notification->token,
                'email'                 => $user->email,
                'password'              => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });

        expect(Hash::check('new-secure-password', $user->fresh()->password))->toBeTrue();
    });

    it('allows login with new password after reset via email OTP flow', function () {
        Notification::fake();

        $user = authUser();
        $newPassword = 'new-secure-password';

        post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user, $newPassword) {
            post('/reset-password', [
                'token'                 => $notification->token,
                'email'                 => $user->email,
                'password'              => $newPassword,
                'password_confirmation' => $newPassword,
            ])->assertRedirect(route('login'));

            return true;
        });

        $user->refresh();
        expect(Hash::check($newPassword, $user->password))->toBeTrue();

        completeEmailLogin($user, $newPassword);

        assertAuthenticated();
    });
});

// ─────────────────────────────────────────────
//  Password reset — validation & negative
// ─────────────────────────────────────────────

describe('password reset validation', function () {
    it('rejects reset with invalid token', function () {
        $user = authUser();

        post('/reset-password', [
            'token'                 => 'invalid-token',
            'email'                 => $user->email,
            'password'              => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertSessionHasErrors(['email']);
    });

    it('rejects reset with expired token', function () {
        Notification::fake();

        $user = authUser();

        post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            \Illuminate\Support\Facades\DB::table('password_reset_tokens')
                ->where('email', $user->email)
                ->update(['created_at' => now()->subHours(2)]);

            post('/reset-password', [
                'token'                 => $notification->token,
                'email'                 => $user->email,
                'password'              => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])->assertSessionHasErrors(['email']);

            return true;
        });
    });

    it('rejects reset when password confirmation does not match', function () {
        Notification::fake();

        $user = authUser();

        post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            post('/reset-password', [
                'token'                 => $notification->token,
                'email'                 => $user->email,
                'password'              => 'new-secure-password',
                'password_confirmation' => 'different-password',
            ])->assertSessionHasErrors(['password']);

            return true;
        });
    });

    it('rejects reset with weak password', function () {
        Notification::fake();

        $user = authUser();

        post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            post('/reset-password', [
                'token'                 => $notification->token,
                'email'                 => $user->email,
                'password'              => 'short',
                'password_confirmation' => 'short',
            ])->assertSessionHasErrors(['password']);

            return true;
        });
    });

    it('rejects reset with missing token', function () {
        $user = authUser();

        post('/reset-password', [
            'token'                 => '',
            'email'                 => $user->email,
            'password'              => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertSessionHasErrors(['token']);
    });

    it('rejects reset with missing email', function () {
        post('/reset-password', [
            'token'                 => 'some-token',
            'email'                 => '',
            'password'              => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertSessionHasErrors(['email']);
    });
});

describe('password reset negative cases', function () {
    it('rejects login with old password after reset', function () {
        Notification::fake();

        $user = authUser();

        post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            post('/reset-password', [
                'token'                 => $notification->token,
                'email'                 => $user->email,
                'password'              => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ]);

            return true;
        });

        loginEmailStep($user->fresh(), 'password')->assertSessionHasErrors(['email']);

        assertGuest();
    });

    it('does not reset password when token email pair is wrong', function () {
        Notification::fake();

        $user = authUser();
        $other = authUser();

        post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($other) {
            post('/reset-password', [
                'token'                 => $notification->token,
                'email'                 => $other->email,
                'password'              => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])->assertSessionHasErrors(['email']);

            return true;
        });

        expect(Hash::check('password', $other->fresh()->password))->toBeTrue();
    });
});

// ─────────────────────────────────────────────
//  Security & access control
// ─────────────────────────────────────────────

describe('forgot password security', function () {
    it('redirects guests from reset password store without valid flow', function () {
        post('/reset-password', [
            'token'                 => 'fake',
            'email'                 => 'test@example.com',
            'password'              => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertSessionHasErrors(['email']);

        assertGuest();
    });

    it('returns error for unregistered email on reset link request', function () {
        Notification::fake();

        post('/forgot-password', ['email' => 'unknown@example.com'])
            ->assertSessionHasErrors(['email']);

        Notification::assertNothingSent();
    });
});
