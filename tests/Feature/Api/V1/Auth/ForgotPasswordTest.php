<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\postJson;

// ─── Shared helpers ───────────────────────────────────────────────────────────

function fpUser(array $attrs = []): User
{
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $user = User::factory()->create(array_merge([
        'status'   => 1,
        'email'    => 'fp.test.' . uniqid() . '@example.com',
        'phone_no' => '9876543210',
        'password' => Hash::make('password123'),
    ], $attrs));

    $user->assignRole('admin');

    return $user;
}

// ─── Success cases ────────────────────────────────────────────────────────────

describe('POST /api/v1/auth/forgot-password — success', function () {

    beforeEach(function () {
        Notification::fake();
    });

    it('returns 200 and sends a reset notification for a registered active email', function () {
        $user = fpUser(['email' => 'reset.me@example.com']);

        postJson('/api/v1/auth/forgot-password', ['email' => 'reset.me@example.com'])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'If your email address is registered and your account is active, you will receive a password reset link shortly. Please check your inbox.',
            ]);

        Notification::assertSentTo($user, ResetPassword::class);
    });

    it('returns 200 with the same generic message for an unregistered email (enumeration protection)', function () {
        postJson('/api/v1/auth/forgot-password', ['email' => 'notregistered@example.com'])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'If your email address is registered and your account is active, you will receive a password reset link shortly. Please check your inbox.',
            ]);

        // No notification should be sent for an unregistered email.
        Notification::assertNothingSent();
    });

    it('is case-insensitive — normalises email to lowercase before lookup', function () {
        $user = fpUser(['email' => 'case.test@example.com']);

        postJson('/api/v1/auth/forgot-password', ['email' => 'CASE.TEST@EXAMPLE.COM'])
            ->assertOk()
            ->assertJson(['success' => true]);

        Notification::assertSentTo($user, ResetPassword::class);
    });
});

// ─── Forbidden cases ──────────────────────────────────────────────────────────

describe('POST /api/v1/auth/forgot-password — forbidden', function () {

    beforeEach(function () {
        Notification::fake();
    });

    it('returns 403 for an inactive user account', function () {
        fpUser(['email' => 'inactive.reset@example.com', 'status' => 0]);

        postJson('/api/v1/auth/forgot-password', ['email' => 'inactive.reset@example.com'])
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Your account is inactive. Please contact support.',
            ]);

        Notification::assertNothingSent();
    });

    it('returns 403 for a soft-deleted user account', function () {
        $user = fpUser(['email' => 'deleted.reset@example.com']);
        $user->update(['deleted_at' => now()]);

        postJson('/api/v1/auth/forgot-password', ['email' => 'deleted.reset@example.com'])
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Your account is inactive. Please contact support.',
            ]);

        Notification::assertNothingSent();
    });
});

// ─── Throttle case ────────────────────────────────────────────────────────────

describe('POST /api/v1/auth/forgot-password — throttle', function () {

    beforeEach(function () {
        Notification::fake();
    });

    it('returns 429 when the password broker throttles a repeated request within 60 seconds', function () {
        $user = fpUser(['email' => 'throttle.reset@example.com']);

        // First request: succeeds.
        postJson('/api/v1/auth/forgot-password', ['email' => 'throttle.reset@example.com'])
            ->assertOk();

        // Second request within the broker's 60-second throttle window: 429.
        postJson('/api/v1/auth/forgot-password', ['email' => 'throttle.reset@example.com'])
            ->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Too many password reset requests. Please wait a moment before trying again.',
            ]);
    });
});

// ─── Validation cases ─────────────────────────────────────────────────────────

describe('POST /api/v1/auth/forgot-password — validation', function () {

    it('returns 422 when the email field is missing', function () {
        postJson('/api/v1/auth/forgot-password', [])
            ->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ])
            ->assertJsonStructure(['data' => ['email']]);
    });

    it('returns 422 when the email format is invalid', function () {
        postJson('/api/v1/auth/forgot-password', ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ])
            ->assertJsonStructure(['data' => ['email']]);
    });
});
