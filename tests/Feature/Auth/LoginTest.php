<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

// ─────────────────────────────────────────────
//  Login page
// ─────────────────────────────────────────────

describe('login page', function () {
    it('renders the login screen for guests', function () {
        get('/login')->assertOk()->assertViewIs('auth.login');
    });

    it('redirects authenticated users away from login', function () {
        actingAs(authUser())->get('/login')->assertRedirect();
    });
});

// ─────────────────────────────────────────────
//  Email login — success
// ─────────────────────────────────────────────

describe('email login success', function () {
    it('redirects to OTP verification with valid email credentials', function () {
        $user = authUser();

        loginEmailStep($user)
            ->assertRedirect(route('verify.otp.form'))
            ->assertSessionHas('otp_user_id', $user->id)
            ->assertSessionHas('message');

        assertGuest();

        $user->refresh();
        expect($user->otp_code)->not->toBeNull()
            ->and($user->otp_expires_at)->not->toBeNull();
    });

    it('completes full email login after OTP verification', function () {
        $user = authUser();

        completeEmailLogin($user);

        assertAuthenticated();
        expect(Auth::id())->toBe($user->id);
    });

    it('redirects to dashboard after successful email login', function () {
        $user = authUser();

        completeEmailLogin($user)->assertRedirect(route('dashboard', absolute: false));
    });

    it('redirects to intended URL after successful login', function () {
        $user = authUser();

        get(route('dashboard'))
            ->assertRedirect(route('login'));

        completeEmailLogin($user)->assertRedirect(route('dashboard', absolute: false));
    });
});

// ─────────────────────────────────────────────
//  Email login — validation & negative cases
// ─────────────────────────────────────────────

describe('email login validation', function () {
    it('rejects login with invalid email format', function () {
        post('/login', [
            'email'    => 'not-an-email',
            'password' => 'password',
        ])->assertSessionHasErrors(['email']);

        assertGuest();
    });

    it('rejects login with unregistered email', function () {
        post('/login', [
            'email'    => 'missing@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors(['email']);

        assertGuest();
    });

    it('rejects login with invalid password', function () {
        $user = authUser();

        loginEmailStep($user, 'wrong-password')->assertSessionHasErrors(['email']);

        assertGuest();
    });

    it('rejects login with empty email', function () {
        post('/login', [
            'email'    => '',
            'password' => 'password',
        ])->assertSessionHasErrors(['email']);

        assertGuest();
    });

    it('rejects login with empty password for email', function () {
        $user = authUser();

        post('/login', [
            'email'    => $user->email,
            'password' => '',
        ])->assertSessionHasErrors(['password']);

        assertGuest();
    });

    it('rejects login with password shorter than 6 characters for email', function () {
        $user = authUser();

        post('/login', [
            'email'    => $user->email,
            'password' => '12345',
        ])->assertSessionHasErrors(['password']);

        assertGuest();
    });
});

describe('email login negative cases', function () {
    it('rejects login for inactive user', function () {
        $user = authUser(['status' => 0]);

        loginEmailStep($user)->assertSessionHasErrors(['email']);

        assertGuest();
    });

    it('rejects login for soft-deleted user', function () {
        $user = authUser(['deleted_at' => now()]);

        loginEmailStep($user)->assertSessionHasErrors(['email']);

        assertGuest();
    });

    it('rejects login for hard-deleted user', function () {
        $user = authUser();
        $email = $user->email;
        $user->delete();

        post('/login', [
            'email'    => $email,
            'password' => 'password',
        ])->assertSessionHasErrors(['email']);

        assertGuest();
    });

    it('does not create session before OTP verification', function () {
        $user = authUser();

        loginEmailStep($user);

        assertGuest();
        expect(session('otp_user_id'))->toBe($user->id);
    });
});

// ─────────────────────────────────────────────
//  Dealer phone login
// ─────────────────────────────────────────────

describe('dealer phone login', function () {
    it('logs in dealer with valid mobile number and password', function () {
        $dealer = createDealerUser(['phone_no' => '9123456789']);

        post('/login', [
            'email'    => '9123456789',
            'password' => 'password',
        ])
            ->assertRedirect(route('dashboard', absolute: false));

        assertAuthenticated();
        expect(Auth::id())->toBe($dealer->id);
    });

    it('rejects mobile login for unregistered number', function () {
        post('/login', [
            'email'    => '9000000000',
            'password' => 'password',
        ])->assertSessionHasErrors(['email']);

        assertGuest();
    });

    it('rejects mobile login for non-dealer users', function () {
        $user = authUser(['phone_no' => '9111222333']);

        post('/login', [
            'email'    => '9111222333',
            'password' => 'password',
        ])->assertSessionHasErrors(['email']);

        assertGuest();
    });

    it('rejects mobile login with invalid password', function () {
        createDealerUser(['phone_no' => '9333444555']);

        post('/login', [
            'email'    => '9333444555',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors(['email']);

        assertGuest();
    });

    it('rejects mobile login without password', function () {
        createDealerUser(['phone_no' => '9444555666']);

        post('/login', [
            'email'    => '9444555666',
            'password' => '',
        ])->assertSessionHasErrors(['password']);

        assertGuest();
    });

    it('rejects mobile login for inactive dealer', function () {
        createDealerUser(['phone_no' => '9555666777', 'status' => 0]);

        post('/login', [
            'email'    => '9555666777',
            'password' => 'password',
        ])->assertSessionHasErrors(['email']);

        assertGuest();
    });

    it('rejects invalid mobile number format', function () {
        post('/login', [
            'email'    => '123',
            'password' => 'password',
        ])->assertSessionHasErrors(['email']);

        assertGuest();
    });

    it('logs in dealer with null email using mobile number and password', function () {
        $dealer = createDealerUser(['phone_no' => '9777888999']);
        $dealer->update(['email' => null]);

        post('/login', [
            'email'    => '9777888999',
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        assertAuthenticated();
        expect(Auth::id())->toBe($dealer->id);
    });
});

// ─────────────────────────────────────────────
//  Remember Me
// ─────────────────────────────────────────────

describe('remember me', function () {
    it('stores remember preference in session during email login', function () {
        $user = authUser();

        loginEmailStep($user, 'password', remember: true)
            ->assertSessionHas('remember_me', true);
    });

    it('persists remember token after full email login with remember me', function () {
        $user = authUser();

        completeEmailLogin($user, remember: true);

        assertAuthenticated();
        expect($user->fresh()->remember_token)->not->toBeNull();
    });

    it('persists remember token for dealer phone login with remember me', function () {
        $dealer = createDealerUser(['phone_no' => '9666777888']);

        post('/login', [
            'email'    => '9666777888',
            'password' => 'password',
            'remember' => true,
        ])->assertRedirect(route('dashboard', absolute: false));

        expect($dealer->fresh()->remember_token)->not->toBeNull();
    });
});

// ─────────────────────────────────────────────
//  Logout
// ─────────────────────────────────────────────

describe('logout', function () {
    it('logs out authenticated user and redirects home', function () {
        $user = authUser();

        actingAs($user)->post('/logout')->assertRedirect('/');

        assertGuest();
    });

    it('invalidates session on logout', function () {
        $user = authUser();

        $response = actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        assertGuest();
    });

    it('requires authentication to access logout route', function () {
        post('/logout')->assertRedirect(route('login'));
    });
});

// ─────────────────────────────────────────────
//  Unauthorized access protection
// ─────────────────────────────────────────────

describe('unauthorized access protection', function () {
    it('redirects guests from dashboard to login', function () {
        get(route('dashboard'))->assertRedirect(route('login'));
    });

    it('allows authenticated users to access dashboard', function () {
        $user = authUser();

        actingAs($user)->get(route('dashboard'))->assertOk();
    });

    it('blocks guests from protected profile route', function () {
        get(route('profile.edit'))->assertRedirect(route('login'));
    });
});
