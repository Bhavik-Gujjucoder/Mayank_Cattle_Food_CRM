<?php

use App\Mail\Api\MobileOtpMail;
use App\Models\MobileOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\postJson;

// ─── Shared helpers ───────────────────────────────────────────────────────────

/**
 * Create an active user with an email address for the OTP flow.
 */
function otpUser(array $attrs = []): User
{
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $user = User::factory()->create(array_merge([
        'status'   => 1,
        'email'    => 'otp.test.' . uniqid() . '@example.com',
        'phone_no' => '9876543210',
        'password' => Hash::make('password123'),
    ], $attrs));

    $user->assignRole('admin');

    return $user;
}

/**
 * Perform email login and return the otp_token from the 202 response.
 * Mail is faked so no real email is sent.
 */
function performEmailLogin(User $user): string
{
    $response = postJson('/api/v1/auth/login', [
        'login'    => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(202);

    return $response->json('data.otp_token');
}

/**
 * Overwrite the pending OTP with a known value for deterministic test assertions.
 */
function injectKnownOtp(User $user, string $rawOtp = '123456'): string
{
    MobileOtp::where('user_id', $user->id)->whereNull('used_at')->update([
        'otp' => $rawOtp,
    ]);

    return $rawOtp;
}

// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function () {
    Mail::fake();
});

// ─── API 2: POST /api/v1/auth/otp/verify ─────────────────────────────────────

describe('POST /api/v1/auth/otp/verify', function () {

    it('returns 200 with a Sanctum token when OTP is correct', function () {
        $user     = otpUser();
        $otpToken = performEmailLogin($user);
        $rawOtp   = injectKnownOtp($user);

        postJson('/api/v1/auth/otp/verify', [
            'otp_token' => $otpToken,
            'otp'       => $rawOtp,
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'OTP verified successfully.',
                'data'    => ['token_type' => 'Bearer'],
            ])
            ->assertJsonStructure([
                'data' => ['user', 'access_token', 'token_type'],
            ]);

        expect($user->tokens()->count())->toBe(1);
    });

    it('marks the OTP as used after successful verification (prevents replay)', function () {
        $user     = otpUser();
        $otpToken = performEmailLogin($user);
        $rawOtp   = injectKnownOtp($user);

        postJson('/api/v1/auth/otp/verify', [
            'otp_token' => $otpToken,
            'otp'       => $rawOtp,
        ])->assertOk();

        $record = MobileOtp::where('user_id', $user->id)->first();
        expect($record->used_at)->not->toBeNull();
    });

    it('uses custom device_name for the Sanctum token', function () {
        $user     = otpUser();
        $otpToken = performEmailLogin($user);
        $rawOtp   = injectKnownOtp($user);

        postJson('/api/v1/auth/otp/verify', [
            'otp_token'   => $otpToken,
            'otp'         => $rawOtp,
            'device_name' => 'Android Pixel 9',
        ])->assertOk();

        expect($user->tokens()->first()->name)->toBe('Android Pixel 9');
    });

    it('returns 422 with attempts_remaining when OTP is incorrect', function () {
        $user     = otpUser();
        $otpToken = performEmailLogin($user);

        postJson('/api/v1/auth/otp/verify', [
            'otp_token' => $otpToken,
            'otp'       => '000000', // wrong OTP
        ])
            ->assertUnprocessable()
            ->assertJson(['success' => false])
            ->assertJsonStructure(['data' => ['attempts_remaining']]);
    });

    it('returns 429 when max attempts are reached', function () {
        $user     = otpUser();
        $otpToken = performEmailLogin($user);

        // Force the attempt counter to the limit
        MobileOtp::where('user_id', $user->id)->update([
            'attempts' => config('otp.max_attempts'),
        ]);

        postJson('/api/v1/auth/otp/verify', [
            'otp_token' => $otpToken,
            'otp'       => '999999',
        ])->assertStatus(429);
    });

    it('returns 410 when the OTP has expired', function () {
        $user     = otpUser();
        $otpToken = performEmailLogin($user);

        // Expire the OTP record
        MobileOtp::where('user_id', $user->id)->update([
            'expires_at' => now()->subMinutes(1),
        ]);

        postJson('/api/v1/auth/otp/verify', [
            'otp_token' => $otpToken,
            'otp'       => '123456',
        ])->assertStatus(410);
    });

    it('returns 404 when no pending OTP session exists', function () {
        $user = otpUser();
        // Generate a valid otp_token manually without creating an OTP record
        $otpToken = encrypt(['user_id' => $user->id, 'purpose' => 'mobile_otp']);

        postJson('/api/v1/auth/otp/verify', [
            'otp_token' => $otpToken,
            'otp'       => '123456',
        ])->assertNotFound();
    });

    it('returns 401 when otp_token is tampered or invalid', function () {
        postJson('/api/v1/auth/otp/verify', [
            'otp_token' => 'totally-invalid-token',
            'otp'       => '123456',
        ])->assertUnauthorized();
    });

    it('returns 403 when user account is inactive', function () {
        $user     = otpUser();
        $otpToken = performEmailLogin($user);

        // Deactivate the user after login
        $user->update(['status' => 0]);

        postJson('/api/v1/auth/otp/verify', [
            'otp_token' => $otpToken,
            'otp'       => '123456',
        ])->assertForbidden();
    });

    it('returns 422 when required fields are missing', function () {
        postJson('/api/v1/auth/otp/verify', [])
            ->assertUnprocessable()
            ->assertJsonStructure(['data' => ['otp_token', 'otp']]);
    });

    it('returns 422 when otp is not exactly 6 digits', function () {
        $user     = otpUser();
        $otpToken = performEmailLogin($user);

        postJson('/api/v1/auth/otp/verify', [
            'otp_token' => $otpToken,
            'otp'       => '12345', // 5 digits
        ])
            ->assertUnprocessable()
            ->assertJsonStructure(['data' => ['otp']]);
    });
});

// ─── API 3: POST /api/v1/auth/otp/resend ─────────────────────────────────────

describe('POST /api/v1/auth/otp/resend', function () {

    it('returns 200 with a new otp_token when resend succeeds', function () {
        $user            = otpUser();
        $originalToken   = performEmailLogin($user);

        // Advance time past the cooldown so resend is allowed
        \Carbon\Carbon::setTestNow(now()->addSeconds(config('otp.resend_cooldown_seconds') + 1));

        $response = postJson('/api/v1/auth/otp/resend', [
            'otp_token' => $originalToken,
        ]);

        \Carbon\Carbon::setTestNow(); // reset

        $response
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => ['otp_token', 'expires_in_seconds', 'resend_attempts_remaining'],
            ]);
    });

    it('queues a new MobileOtpMail on successful resend', function () {
        $user  = otpUser();
        $token = performEmailLogin($user);

        Mail::fake(); // reset fake after login
        \Carbon\Carbon::setTestNow(now()->addSeconds(config('otp.resend_cooldown_seconds') + 1));

        postJson('/api/v1/auth/otp/resend', ['otp_token' => $token])->assertOk();

        \Carbon\Carbon::setTestNow();

        Mail::assertQueued(MobileOtpMail::class);
    });

    it('invalidates the old OTP code after resend (only the new code is accepted)', function () {
        $user          = otpUser();
        $originalToken = performEmailLogin($user);

        // Inject a known OTP code for the original session
        injectKnownOtp($user, '111111');

        \Carbon\Carbon::setTestNow(now()->addSeconds(config('otp.resend_cooldown_seconds') + 1));

        $newToken = postJson('/api/v1/auth/otp/resend', [
            'otp_token' => $originalToken,
        ])->json('data.otp_token');

        \Carbon\Carbon::setTestNow();

        // Inject the new session's OTP as a known value
        $newRawOtp = injectKnownOtp($user, '222222');

        // Old 6-digit code (111111) is no longer valid — that row was deleted on resend
        postJson('/api/v1/auth/otp/verify', [
            'otp_token' => $newToken,
            'otp'       => '111111',
        ])->assertUnprocessable(); // 422 — wrong code

        // New 6-digit code succeeds
        postJson('/api/v1/auth/otp/verify', [
            'otp_token' => $newToken,
            'otp'       => $newRawOtp,
        ])->assertOk();
    });

    it('returns 429 with seconds_remaining when cooldown has not elapsed', function () {
        $user  = otpUser();
        $token = performEmailLogin($user);

        // Attempt resend immediately (cooldown still active)
        postJson('/api/v1/auth/otp/resend', ['otp_token' => $token])
            ->assertStatus(429)
            ->assertJsonStructure(['data' => ['seconds_remaining']]);
    });

    it('returns 429 when the max resend limit is reached', function () {
        $user  = otpUser();
        $token = performEmailLogin($user);

        // Force resend_count to the limit
        MobileOtp::where('user_id', $user->id)->update([
            'resend_count' => config('otp.max_resend_attempts'),
            'last_sent_at' => now()->subSeconds(config('otp.resend_cooldown_seconds') + 1),
        ]);

        postJson('/api/v1/auth/otp/resend', ['otp_token' => $token])
            ->assertStatus(429)
            ->assertJson(['message' => 'Maximum resend limit reached. Please log in again.']);
    });

    it('returns 401 when otp_token is invalid', function () {
        postJson('/api/v1/auth/otp/resend', [
            'otp_token' => 'not-a-real-token',
        ])->assertUnauthorized();
    });

    it('returns 403 when user account is inactive', function () {
        $user  = otpUser();
        $token = performEmailLogin($user);
        $user->update(['status' => 0]);

        \Carbon\Carbon::setTestNow(now()->addSeconds(config('otp.resend_cooldown_seconds') + 1));

        postJson('/api/v1/auth/otp/resend', ['otp_token' => $token])
            ->assertForbidden();

        \Carbon\Carbon::setTestNow();
    });

    it('returns 422 when otp_token field is missing', function () {
        postJson('/api/v1/auth/otp/resend', [])
            ->assertUnprocessable()
            ->assertJsonStructure(['data' => ['otp_token']]);
    });
});
