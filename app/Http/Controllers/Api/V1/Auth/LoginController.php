<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\Api\V1\Auth\LoginService;
use App\Services\Api\V1\Auth\OtpService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Mobile login endpoint — authenticates by email or phone number.
 *
 * Two-branch flow (v1):
 *
 *   ┌─ Email + Password ──────────────────────────────────────────────────┐
 *   │  POST /api/v1/auth/login                                            │
 *   │    → credentials verified → OTP generated & emailed → 202          │
 *   │  POST /api/v1/auth/otp/verify  (OtpController)                     │
 *   │    → OTP verified → Sanctum token issued → 200                     │
 *   └─────────────────────────────────────────────────────────────────────┘
 *
 *   ┌─ Phone + Password ──────────────────────────────────────────────────┐
 *   │  POST /api/v1/auth/login                                            │
 *   │    → credentials verified → Sanctum token issued immediately → 200 │
 *   └─────────────────────────────────────────────────────────────────────┘
 *
 * The web app's session-based login (AuthenticatedSessionController) and its
 * OTP flow are separate and not affected by this controller.
 *
 * @see LoginService   Credential resolution and account eligibility
 * @see OtpService     OTP generation, email dispatch, and otp_token issuance
 * @see OtpController  Handles /otp/verify and /otp/resend
 */
class LoginController extends Controller
{
    /**
     * Authenticate a mobile user.
     *
     * Detects login type from the 'login' field:
     *   - Valid email format  → initiates OTP verification flow (202)
     *   - Phone number format → issues Sanctum token immediately (200)
     *
     * Email path response (202):
     *   { success, message, data: { otp_required: true, otp_token, expires_in_seconds } }
     *
     * Phone path response (200):
     *   { success, message, data: { user, access_token, token_type } }
     *
     * Failure (401):
     *   { success: false, message, data: null }
     */
    public function store(LoginRequest $request, LoginService $loginService, OtpService $otpService): JsonResponse
    {
        $login = $request->string('login')->toString();

        // Verify credentials and account eligibility (active status, not soft-deleted).
        $user = $loginService->authenticate($login, $request->string('password')->toString());

        if (! $user) {
            // Generic message — does not reveal whether the email/phone exists.
            return ApiResponse::error('The provided credentials are incorrect.', null, 401);
        }

        // ── Email login: initiate OTP verification ──────────────────────────
        // The Sanctum token is NOT issued here; it is issued by OtpController
        // only after the user successfully verifies the OTP.
        if (filter_var(trim($login), FILTER_VALIDATE_EMAIL)) {
            // Generate OTP, store hash, send email, return encrypted otp_token.
            $otpToken = $otpService->createAndSend($user);

            return ApiResponse::success(
                'OTP sent to your registered email address. Please verify to continue.',
                [
                    'otp_required'       => true,
                    'otp_token'          => $otpToken,   // client must store and forward this
                    'expires_in_seconds' => config('otp.expiry_minutes') * 60,
                ],
                202 // Accepted: action initiated but not complete yet
            );
        }

        // ── Phone login: issue Sanctum token immediately ─────────────────────
        // Revoke any stale tokens before creating a new one.
        $user->tokens()->delete();

        $tokenName   = $request->input('device_name', 'mobile-app');
        $accessToken = $user->createToken($tokenName)->plainTextToken;

        return ApiResponse::success('Login successful.', [
            'user'         => new UserResource($user),
            'access_token' => $accessToken,
            'token_type'   => 'Bearer',
        ]);
    }
}
