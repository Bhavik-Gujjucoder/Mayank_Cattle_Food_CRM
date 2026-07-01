<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

/**
 * API 4 — Forgot Password (send reset link by email).
 *
 * Flow:
 *   POST /api/v1/auth/forgot-password
 *     → validate email format
 *     → look up user by email
 *     → if not found        → return generic 200 (user enumeration protection)
 *     → if inactive/deleted → return 403 Forbidden
 *     → if active           → generate token, send reset email → return 200
 *     → if throttled        → return 429 Too Many Requests
 *
 * The password reset link in the email points to the web reset-password page
 * (APP_URL/reset-password/{token}?email=...). The user completes the reset
 * in a browser; after resetting they can log in through the mobile API.
 *
 * This controller does NOT modify the web PasswordResetLinkController or
 * any existing authentication functionality.
 *
 * @see \App\Http\Controllers\Auth\PasswordResetLinkController  Web equivalent (session-based)
 * @see \App\Http\Controllers\Auth\NewPasswordController        Web reset form handler
 */
class ForgotPasswordController extends Controller
{
    /**
     * Send a password reset link to the provided email address.
     *
     * HTTP responses:
     *   200 — link sent (or email not found — same message for enumeration protection)
     *   403 — account is inactive or suspended
     *   422 — email field failed format validation
     *   429 — too many reset requests; try again after the throttle window
     *   500 — unexpected server error
     *
     * Security note: we return an identical success message whether the email
     * exists in the system or not. This prevents attackers from using this
     * endpoint to enumerate registered email addresses.
     *
     * Exception: inactive accounts return 403. An attacker already needs to
     * know a valid email to reach this branch, and the 403 message does not
     * reveal whether other emails are registered.
     */
    public function store(ForgotPasswordRequest $request): JsonResponse
    {
        $email = strtolower(trim($request->input('email')));

        // ── Step 1: Look up user by email ────────────────────────────────────
        // We query manually (rather than relying solely on Password::sendResetLink)
        // so we can inspect the account status before dispatching the email.
        $user = User::where('email', $email)->first();

        // ── Step 2: Non-existent email → generic success (enumeration shield) ─
        // Return the same message as a successful send so an attacker cannot
        // determine whether an email address is registered.
        if (! $user) {
            return $this->genericSuccessResponse();
        }

        // ── Step 3: Inactive / suspended / deleted account → 403 ─────────────
        // We do reveal this state because the user themselves would know if their
        // account is inactive, and returning 403 is necessary for a good UX.
        if ((int) $user->status !== 1 || $user->deleted_at !== null) {
            return ApiResponse::error(
                'Your account is inactive. Please contact support.',
                null,
                403
            );
        }

        // ── Step 4: Delegate to Laravel's password broker ─────────────────────
        // Password::sendResetLink() handles:
        //   - Generating and hashing the reset token
        //   - Inserting a row into password_reset_tokens
        //   - Calling $user->sendPasswordResetNotification($token) → queues email
        //   - Respecting the per-email throttle (config auth.passwords.users.throttle)
        $status = Password::sendResetLink(['email' => $email]);

        // ── Step 5: Map broker status to HTTP response ────────────────────────
        return match ($status) {

            // Token created and reset email dispatched successfully.
            Password::RESET_LINK_SENT =>
                $this->genericSuccessResponse(),

            // Too many reset requests for this email within the throttle window.
            // config('auth.passwords.users.throttle') defines the window in seconds.
            Password::RESET_THROTTLED =>
                ApiResponse::error(
                    'Too many password reset requests. Please wait a moment before trying again.',
                    null,
                    429
                ),

            // Any other unexpected status from the broker.
            default =>
                ApiResponse::error(
                    'Unable to send password reset link. Please try again later.',
                    null,
                    500
                ),
        };
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * Return the generic "link sent" response.
     *
     * Using one method ensures the body and status code are identical whether
     * the email is registered or not, preventing user enumeration.
     */
    private function genericSuccessResponse(): JsonResponse
    {
        return ApiResponse::success(
            'If your email address is registered and your account is active, ' .
            'you will receive a password reset link shortly. Please check your inbox.'
        );
    }
}
