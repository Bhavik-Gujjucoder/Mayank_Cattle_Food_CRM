<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

/*
|--------------------------------------------------------------------------
| Email OTP second-factor authentication
|--------------------------------------------------------------------------
| This application uses email OTP as a second authentication step after
| password verification for email-based logins. Dealer mobile logins skip OTP.
*/

// ─────────────────────────────────────────────
//  OTP enabled on email login (second factor)
// ─────────────────────────────────────────────

describe('otp second factor on email login', function () {
    it('enables OTP step after valid email password authentication', function () {
        $user = authUser();

        loginEmailStep($user)
            ->assertRedirect(route('verify.otp.form'))
            ->assertSessionHas('otp_user_id', $user->id);

        $user->refresh();
        expect($user->otp_code)->toMatch('/^\d{6}$/')
            ->and($user->otp_expires_at->isFuture())->toBeTrue();
    });

    it('does not authenticate user until OTP is verified', function () {
        $user = authUser();

        loginEmailStep($user);

        assertGuest();
        get(route('dashboard'))->assertRedirect(route('login'));
    });

    it('skips OTP for dealer mobile login (single-factor flow)', function () {
        createDealerUser(['phone_no' => '9888777666']);

        post('/login', [
            'email'    => '9888777666',
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        assertAuthenticated();
        expect(session('otp_user_id'))->toBeNull();
    });
});

// ─────────────────────────────────────────────
//  OTP verification — success
// ─────────────────────────────────────────────

describe('otp verification success', function () {
    it('verifies valid OTP and authenticates user', function () {
        $user = authUser();

        loginEmailStep($user);

        verifyLoginOtp($user);

        assertAuthenticated();
        expect(Auth::id())->toBe($user->id);
    });

    it('clears OTP fields after successful verification', function () {
        $user = authUser();

        loginEmailStep($user);
        verifyLoginOtp($user);

        $user->refresh();
        expect($user->otp_code)->toBeNull()
            ->and($user->otp_expires_at)->toBeNull();
    });

    it('clears OTP session keys after successful verification', function () {
        $user = authUser();

        loginEmailStep($user);
        verifyLoginOtp($user);

        expect(session('otp_user_id'))->toBeNull()
            ->and(session('remember_me'))->toBeNull();
    });

    it('redirects to dashboard after OTP verification', function () {
        $user = authUser();

        loginEmailStep($user);
        verifyLoginOtp($user)->assertRedirect(route('dashboard', absolute: false));
    });
});

// ─────────────────────────────────────────────
//  OTP verification — validation & negative
// ─────────────────────────────────────────────

describe('otp verification validation', function () {
    it('rejects missing OTP', function () {
        $user = authUser();

        loginEmailStep($user);

        post(route('verify.otp'), ['otp_combined' => ''])
            ->assertSessionHasErrors(['otp_combined']);

        assertGuest();
    });

    it('rejects OTP with wrong number of digits', function () {
        $user = authUser();

        loginEmailStep($user);

        post(route('verify.otp'), ['otp_combined' => '12345'])
            ->assertSessionHasErrors(['otp_combined']);

        assertGuest();
    });

    it('rejects invalid OTP code', function () {
        $user = authUser();

        loginEmailStep($user);

        post(route('verify.otp'), ['otp_combined' => '000000'])
            ->assertSessionHasErrors(['otp_combined']);

        assertGuest();
    });

    it('rejects expired OTP', function () {
        $user = authUser();

        loginEmailStep($user);

        $user->update(['otp_expires_at' => now()->subMinute()]);

        verifyLoginOtp($user)->assertSessionHasErrors(['otp_combined']);

        assertGuest();
    });
});

describe('otp verification negative cases', function () {
    it('rejects OTP verification without login session', function () {
        post(route('verify.otp'), ['otp_combined' => '123456'])
            ->assertSessionHasErrors(['otp_combined']);

        assertGuest();
    });

    it('rejects OTP when session user no longer exists', function () {
        $user = authUser();

        loginEmailStep($user);
        $user->delete();

        post(route('verify.otp'), ['otp_combined' => '123456'])
            ->assertSessionHasErrors(['otp_combined']);

        assertGuest();
    });
});

// ─────────────────────────────────────────────
//  Protected route access
// ─────────────────────────────────────────────

describe('protected route access', function () {
    it('blocks dashboard access before OTP verification', function () {
        $user = authUser();

        loginEmailStep($user);

        get(route('dashboard'))->assertRedirect(route('login'));
        assertGuest();
    });

    it('allows dashboard access after OTP verification', function () {
        $user = authUser();

        completeEmailLogin($user);

        get(route('dashboard'))->assertOk();
    });

    it('renders OTP verification form', function () {
        get(route('verify.otp.form'))->assertOk()->assertViewIs('auth.verify_otp');
    });
});

// ─────────────────────────────────────────────
//  Resend OTP (recovery flow)
// ─────────────────────────────────────────────

describe('resend otp', function () {
    it('regenerates OTP when resend is requested', function () {
        $user = authUser();

        loginEmailStep($user);
        $oldOtp = $user->fresh()->otp_code;

        post(route('resend.otp'))
            ->assertRedirect(route('verify.otp.form'))
            ->assertSessionHas('message');

        $newOtp = $user->fresh()->otp_code;
        expect($newOtp)->not->toBe($oldOtp)
            ->and($user->fresh()->otp_expires_at->isFuture())->toBeTrue();
    });

    it('allows login with regenerated OTP after resend', function () {
        $user = authUser();

        loginEmailStep($user);
        post(route('resend.otp'));

        verifyLoginOtp($user);

        assertAuthenticated();
    });

    it('redirects to login when resend requested without OTP session', function () {
        post(route('resend.otp'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
    });

    it('redirects to login when resend session user is missing', function () {
        $user = authUser();

        loginEmailStep($user);
        $user->delete();

        post(route('resend.otp'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
    });
});

// ─────────────────────────────────────────────
//  Security
// ─────────────────────────────────────────────

describe('otp security', function () {
    it('does not accept OTP without prior password authentication', function () {
        $user = authUser();
        $user->update([
            'otp_code'       => '654321',
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        post(route('verify.otp'), ['otp_combined' => '654321'])
            ->assertSessionHasErrors(['otp_combined']);

        assertGuest();
    });

    it('invalidates used OTP after successful verification', function () {
        $user = authUser();

        loginEmailStep($user);
        $otp = (string) $user->fresh()->otp_code;

        verifyLoginOtp($user, $otp);
        Auth::logout();

        loginEmailStep($user);
        post(route('verify.otp'), ['otp_combined' => $otp])
            ->assertSessionHasErrors(['otp_combined']);
    });

    it('applies remember me from OTP session on successful verification', function () {
        $user = authUser();

        loginEmailStep($user, remember: true);
        verifyLoginOtp($user);

        assertAuthenticated();
        expect($user->fresh()->remember_token)->not->toBeNull();
    });

    it('completes full login flow with email password and OTP', function () {
        $user = authUser();

        loginEmailStep($user)
            ->assertRedirect(route('verify.otp.form'));

        verifyLoginOtp($user)
            ->assertRedirect(route('dashboard', absolute: false));

        assertAuthenticated();
        get(route('dashboard'))->assertOk();
    });
});
