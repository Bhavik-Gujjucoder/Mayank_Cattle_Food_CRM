<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\postJson;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Mail::fake(); // prevent real OTP emails during tests
});

function apiLoginUser(array $attrs = []): User
{
    $user = User::factory()->create(array_merge([
        'status' => 1,
        'phone_no' => '9876543210',
        'password' => Hash::make('password123'),
    ], $attrs));

    $user->assignRole('admin');

    return $user;
}

// ─── Email login (OTP flow) ───────────────────────────────────────────────────

describe('POST /api/v1/auth/login — email path (OTP flow)', function () {

    it('returns 202 with otp_token when logging in with email and password', function () {
        $user = apiLoginUser(['email' => 'mobile.api@example.com']);

        postJson('/api/v1/auth/login', [
            'login'    => 'mobile.api@example.com',
            'password' => 'password123',
        ])
            ->assertStatus(202)
            ->assertJson([
                'success' => true,
                'data'    => ['otp_required' => true],
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['otp_required', 'otp_token', 'expires_in_seconds'],
            ]);

        // No Sanctum token yet — it is issued only after OTP verification.
        expect($user->tokens()->count())->toBe(0);
    });

    it('queues a MobileOtpMail when email login succeeds', function () {
        apiLoginUser(['email' => 'otp.mail@example.com']);

        postJson('/api/v1/auth/login', [
            'login'    => 'otp.mail@example.com',
            'password' => 'password123',
        ])->assertStatus(202);

        Mail::assertQueued(\App\Mail\Api\MobileOtpMail::class);
    });

    it('stores an OTP record in mobile_otps on email login', function () {
        $user = apiLoginUser(['email' => 'otp.db@example.com']);

        postJson('/api/v1/auth/login', [
            'login'    => 'otp.db@example.com',
            'password' => 'password123',
        ])->assertStatus(202);

        expect(\App\Models\MobileOtp::where('user_id', $user->id)->count())->toBe(1);
    });

    it('returns 401 for invalid email credentials', function () {
        apiLoginUser(['email' => 'wrong-creds@example.com']);

        postJson('/api/v1/auth/login', [
            'login'    => 'wrong-creds@example.com',
            'password' => 'not-the-password',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
            ]);
    });

    it('returns 401 for inactive email users', function () {
        apiLoginUser(['email' => 'inactive@example.com', 'status' => 0]);

        postJson('/api/v1/auth/login', [
            'login'    => 'inactive@example.com',
            'password' => 'password123',
        ])->assertUnauthorized();
    });

    it('returns 401 for soft-deleted email users', function () {
        $user = apiLoginUser(['email' => 'deleted@example.com']);
        $user->update(['deleted_at' => now()]);

        postJson('/api/v1/auth/login', [
            'login'    => 'deleted@example.com',
            'password' => 'password123',
        ])->assertUnauthorized();
    });
});

// ─── Phone login (direct token) ───────────────────────────────────────────────

describe('POST /api/v1/auth/login — phone path (direct token)', function () {

    it('returns 200 with a Sanctum token when logging in with phone number', function () {
        $user = apiLoginUser(['email' => null, 'phone_no' => '9123456789']);

        postJson('/api/v1/auth/login', [
            'login'    => '9123456789',
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Login successful.',
                'data'    => ['token_type' => 'Bearer'],
            ])
            ->assertJsonStructure([
                'data' => ['user', 'access_token', 'token_type'],
            ]);

        expect($user->tokens()->count())->toBe(1);
    });

    it('uses custom device_name for the Sanctum token on phone login', function () {
        $user = apiLoginUser(['email' => null, 'phone_no' => '9111111111']);

        postJson('/api/v1/auth/login', [
            'login'       => '9111111111',
            'password'    => 'password123',
            'device_name' => 'iPhone 15',
        ])->assertOk();

        expect($user->tokens()->first()->name)->toBe('iPhone 15');
    });

    it('returns 401 for invalid phone credentials', function () {
        apiLoginUser(['email' => null, 'phone_no' => '9000000001']);

        postJson('/api/v1/auth/login', [
            'login'    => '9000000001',
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    });

    it('returns 401 for inactive phone users', function () {
        apiLoginUser(['email' => null, 'phone_no' => '9000000002', 'status' => 0]);

        postJson('/api/v1/auth/login', [
            'login'    => '9000000002',
            'password' => 'password123',
        ])->assertUnauthorized();
    });
});

// ─── Shared validation ────────────────────────────────────────────────────────

describe('POST /api/v1/auth/login — validation', function () {

    it('returns 422 when login format is invalid (not email or phone)', function () {
        postJson('/api/v1/auth/login', [
            'login'    => 'not-an-email-or-phone',
            'password' => 'password123',
        ])
            ->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
            ])
            ->assertJsonStructure(['data' => ['login']]);
    });

    it('returns 422 when required fields are missing', function () {
        postJson('/api/v1/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonStructure(['data' => ['login', 'password']]);
    });
});
