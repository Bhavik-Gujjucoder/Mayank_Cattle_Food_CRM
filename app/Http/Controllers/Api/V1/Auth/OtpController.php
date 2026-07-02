<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\Api\OtpException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ResendOtpRequest;
use App\Http\Requests\Api\V1\Auth\VerifyOtpRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\Api\V1\Auth\OtpService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Handles the two-step OTP phase of the Mobile API email login flow.
 *
 * Flow summary:
 *   POST /api/v1/auth/login          (email)  → 202 + otp_token
 *   POST /api/v1/auth/otp/resend     (optional, up to max_resend_attempts times)
 *   POST /api/v1/auth/otp/verify     → 200 + Sanctum Bearer token
 *
 * Phone login bypasses this controller entirely and receives a token directly
 * from LoginController.
 *
 * @see OtpService  All business logic (generation, hashing, expiry, limits)
 * @see OtpException Typed failure constants mapped to HTTP responses here
 */
class OtpController extends Controller
{
    // ─── API 2: Verify OTP ───────────────────────────────────────────────────

    /**
     * Verify the submitted OTP and issue a Sanctum Bearer token on success.
     *
     * The otp_token (from LoginController or ResendOtpRequest) is decrypted to
     * identify the user; no user_id is accepted directly in the request body.
     *
     * Success (200):  { success, message, data: { user, access_token, token_type } }
     * Failure (4xx):  { success: false, message, data: null }
     *
     * HTTP codes:
     *   200 — OTP verified, token issued
     *   401 — otp_token invalid or tampered
     *   403 — user account inactive / suspended
     *   404 — no pending OTP session found
     *   410 — OTP has expired
     *   422 — OTP incorrect (response includes attempts_remaining)
     *   429 — too many failed attempts; session is locked
     */
    public function verify(VerifyOtpRequest $request, OtpService $otpService): JsonResponse
    {
        // Delegate all OTP logic to the service; catch typed exceptions to build responses.
        try {
            $user = $otpService->verify(
                $request->input('otp_token'),
                $request->input('otp'),
            );
        } catch (OtpException $e) {
            return $this->otpErrorResponse($e);
        }

        // Revoke all existing Sanctum tokens before issuing a new one.
        // Prevents accumulation of stale tokens from previous login sessions.
        $user->tokens()->delete();

        // Create a named personal access token; plain text returned only once.
        $tokenName   = $request->input('device_name', 'mobile-app');
        $accessToken = $user->createToken($tokenName)->plainTextToken;

        return ApiResponse::success('OTP verified successfully.', [
            'user'         => new UserResource($user),
            'access_token' => $accessToken,
            'token_type'   => 'Bearer',
        ]);
    }

    // ─── API 3: Resend OTP ───────────────────────────────────────────────────

    /**
     * Invalidate the current OTP, generate a new one, and resend it by email.
     *
     * A new otp_token is returned — the client MUST replace the stored token
     * with this new one before calling verify. The previous token is invalid.
     *
     * Success (200):  { success, message, data: { otp_token, expires_in_seconds, resend_attempts_remaining } }
     * Failure (4xx):  { success: false, message, data: null | { seconds_remaining } }
     *
     * HTTP codes:
     *   200 — new OTP sent successfully
     *   401 — otp_token invalid or tampered
     *   403 — user account inactive / suspended
     *   404 — no pending OTP session found (must log in again)
     *   429 — cooldown not elapsed, or max resend limit reached
     */
    public function resend(ResendOtpRequest $request, OtpService $otpService): JsonResponse
    {
        try {
            // Service validates cooldown + limit, deletes old OTP, creates new one, queues email.
            $result = $otpService->resend($request->input('otp_token'));
        } catch (OtpException $e) {
            return $this->otpErrorResponse($e);
        }

        return ApiResponse::success('A new OTP has been sent to your registered email address.', [
            'otp_token'                => $result['otp_token'],
            'expires_in_seconds'       => config('otp.expiry_minutes') * 60,
            'resend_attempts_remaining' => $result['resend_attempts_remaining'],
        ]);
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * Map an OtpException type to the correct HTTP response.
     *
     * Keeping this mapping in one place ensures consistent HTTP status codes
     * and messages across verify and resend.
     */
    private function otpErrorResponse(OtpException $e): JsonResponse
    {
        return match ($e->type) {

            OtpException::TOKEN_INVALID =>
                ApiResponse::error('Invalid or expired session. Please log in again.', null, 401),

            OtpException::SESSION_NOT_FOUND =>
                ApiResponse::error('No pending OTP found. Please log in again.', null, 404),

            OtpException::EXPIRED =>
                ApiResponse::error('OTP has expired. Please log in again.', null, 410),

            OtpException::INACTIVE_USER =>
                ApiResponse::error('Your account is inactive. Please contact support.', null, 403),

            OtpException::TOO_MANY_ATTEMPTS =>
                ApiResponse::error('Too many incorrect attempts. Please log in again.', null, 429),

            // Include remaining attempts so the client can show a countdown UI.
            OtpException::INCORRECT =>
                ApiResponse::error(
                    'Incorrect OTP. ' . ($e->context['attempts_remaining'] ?? 0) . ' attempt(s) remaining.',
                    ['attempts_remaining' => $e->context['attempts_remaining'] ?? 0],
                    422
                ),

            // Include seconds_remaining so the client can display a cooldown timer.
            OtpException::COOLDOWN =>
                ApiResponse::error(
                    'Please wait ' . ($e->context['seconds_remaining'] ?? 0) . ' second(s) before requesting a new OTP.',
                    ['seconds_remaining' => $e->context['seconds_remaining'] ?? 0],
                    429
                ),

            OtpException::RESEND_LIMIT =>
                ApiResponse::error('Maximum resend limit reached. Please log in again.', null, 429),

            default =>
                ApiResponse::error('OTP operation failed. Please try again.', null, 400),
        };
    }
}
