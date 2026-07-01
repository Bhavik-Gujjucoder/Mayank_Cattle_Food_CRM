<?php

namespace App\Exceptions\Api;

use RuntimeException;

/**
 * Thrown by OtpService to signal a named failure in the OTP lifecycle.
 *
 * The controller catches this and maps $type to the appropriate HTTP status
 * and user-facing message. Optional $context carries extra data (e.g. the
 * number of remaining attempts or seconds left in a cooldown).
 *
 * Usage:
 *   throw new OtpException(OtpException::EXPIRED);
 *   throw new OtpException(OtpException::INCORRECT, ['attempts_remaining' => 3]);
 *   throw new OtpException(OtpException::COOLDOWN,  ['seconds_remaining'  => 45]);
 */
class OtpException extends RuntimeException
{
    // ─── Failure type constants ───────────────────────────────────────────────

    /** otp_token could not be decrypted or has an invalid payload. → 401 */
    public const TOKEN_INVALID = 'token_invalid';

    /** No pending (unused) OTP row found for the user. → 404 */
    public const SESSION_NOT_FOUND = 'session_not_found';

    /** OTP row exists but expires_at has passed. → 410 */
    public const EXPIRED = 'expired';

    /** User account is inactive, suspended, or soft-deleted. → 403 */
    public const INACTIVE_USER = 'inactive_user';

    /** Failed attempts have reached config('otp.max_attempts'). → 429 */
    public const TOO_MANY_ATTEMPTS = 'too_many_attempts';

    /** OTP submitted but did not match the stored hash. → 422 */
    public const INCORRECT = 'incorrect';

    /** Resend requested before the cooldown window has elapsed. → 429 */
    public const COOLDOWN = 'cooldown';

    /** Resend count has reached config('otp.max_resend_attempts'). → 429 */
    public const RESEND_LIMIT = 'resend_limit';

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param  string  $type     One of the constants above.
     * @param  array   $context  Extra data the controller may include in the response.
     */
    public function __construct(
        public readonly string $type,
        public readonly array $context = [],
    ) {
        parent::__construct($type);
    }
}
