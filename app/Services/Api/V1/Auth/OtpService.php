<?php

namespace App\Services\Api\V1\Auth;

use App\Exceptions\Api\OtpException;
use App\Mail\Api\MobileOtpMail;
use App\Models\MobileOtp;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Mobile API OTP business logic.
 *
 * Handles the full OTP lifecycle:
 *   1. createAndSend() — called by LoginController after a successful email login.
 *   2. verify()        — called by OtpController to validate the submitted OTP.
 *   3. resend()        — called by OtpController to regenerate and resend the OTP.
 *
 * Identity across API calls is established via an encrypted otp_token that
 * embeds the user_id. The client stores this token and sends it with every
 * OTP-related request. It is never exposed as a raw user_id.
 *
 * OTPs are stored in plain text in mobile_otps for straightforward development
 * and testing verification. Do not log the OTP value.
 */
class OtpService
{
    // ─── Public API ──────────────────────────────────────────────────────────

    /**
     * Generate a new OTP for an email-login user, persist it, send the email,
     * and return an encrypted otp_token the client must use for verify/resend.
     *
     * Any existing pending (unused) OTP for the user is deleted first so that
     * only one active session exists at a time.
     */
    public function createAndSend(User $user): string
    {
        // Invalidate any existing unused OTP — only one session at a time.
        MobileOtp::where('user_id', $user->id)->whereNull('used_at')->delete();

        $otp = $this->generateOtp();

        MobileOtp::create([
            'user_id'      => $user->id,
            'otp'          => (string) $otp,
            'expires_at'   => now()->addMinutes(config('otp.expiry_minutes')),
            'last_sent_at' => now(),
        ]);

        // Queue the email — does NOT block the HTTP response.
        Mail::to($user->email)->queue(new MobileOtpMail($otp, $user));

        return $this->issueOtpToken($user->id);
    }

    /**
     * Verify a submitted OTP against the stored plain-text value.
     *
     * Returns the authenticated User on success.
     * Throws OtpException with a specific type constant on any failure.
     *
     * @throws OtpException
     */
    public function verify(string $otpToken, string $otp): User
    {
        // Decrypt and validate the otp_token to extract the user_id.
        $userId = $this->resolveOtpToken($otpToken);

        // Ensure the user still exists and is active.
        $user = $this->resolveActiveUser($userId);

        // Fetch the pending OTP record.
        $record = $this->getPendingOtp($userId);

        // Reject if the OTP validity window has passed.
        if ($record->isExpired()) {
            $record->delete(); // clean up immediately; user must log in again
            throw new OtpException(OtpException::EXPIRED);
        }

        // Reject if the user has exhausted all allowed attempts.
        if ($record->attempts >= config('otp.max_attempts')) {
            throw new OtpException(OtpException::TOO_MANY_ATTEMPTS);
        }

        if ((string) $record->otp !== (string) $otp) {
            $record->increment('attempts');
            $remaining = $record->fresh()->attemptsRemaining();

            throw new OtpException(OtpException::INCORRECT, [
                'attempts_remaining' => $remaining,
            ]);
        }

        // Mark as used — prevents replay attacks.
        $record->update(['used_at' => now()]);

        return $user;
    }

    /**
     * Regenerate the OTP for an existing session and resend it.
     *
     * Validates the cooldown period and the resend count limit before
     * proceeding. Deletes the old OTP row and creates a fresh one.
     *
     * Returns an array with the new otp_token and resend_attempts_remaining.
     *
     * @return array{otp_token: string, resend_attempts_remaining: int}
     * @throws OtpException
     */
    public function resend(string $otpToken): array
    {
        $userId = $this->resolveOtpToken($otpToken);

        $user = $this->resolveActiveUser($userId);

        $record = $this->getPendingOtp($userId);

        // Enforce cooldown: prevent spamming resend within the configured window.
        if ($record->last_sent_at !== null) {
            // Compute from last_sent_at → now to always get a positive elapsed value.
            $secondsElapsed   = (int) $record->last_sent_at->diffInSeconds(now());
            $cooldown         = config('otp.resend_cooldown_seconds');
            $secondsRemaining = $cooldown - $secondsElapsed;

            if ($secondsRemaining > 0) {
                throw new OtpException(OtpException::COOLDOWN, [
                    'seconds_remaining' => $secondsRemaining,
                ]);
            }
        }

        // Enforce resend limit: prevent unlimited resends per session.
        if ($record->resend_count >= config('otp.max_resend_attempts')) {
            throw new OtpException(OtpException::RESEND_LIMIT);
        }

        // Carry the incremented resend_count forward to the new record.
        $newResendCount = $record->resend_count + 1;

        // Delete the old OTP row — the old otp_token is now invalid.
        $record->delete();

        // Generate and persist the new OTP.
        $otp = $this->generateOtp();

        MobileOtp::create([
            'user_id'      => $userId,
            'otp'          => (string) $otp,
            'expires_at'   => now()->addMinutes(config('otp.expiry_minutes')),
            'last_sent_at' => now(),
            'resend_count' => $newResendCount,
        ]);

        // Queue the email.
        Mail::to($user->email)->queue(new MobileOtpMail($otp, $user));

        // Audit log: record resend event without logging the plain OTP.
        Log::info('Mobile OTP resent', [
            'user_id'      => $userId,
            'resend_count' => $newResendCount,
        ]);

        return [
            'otp_token'                => $this->issueOtpToken($userId),
            'resend_attempts_remaining' => config('otp.max_resend_attempts') - $newResendCount,
        ];
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * Generate a cryptographically random 6-digit OTP.
     * Returns an integer so leading-zero edge cases are avoided in display.
     */
    private function generateOtp(): int
    {
        return random_int(100000, 999999);
    }

    /**
     * Wrap the user_id in an encrypted, signed payload.
     * The "purpose" field prevents tokens from one flow being reused elsewhere.
     */
    private function issueOtpToken(int $userId): string
    {
        return encrypt([
            'user_id' => $userId,
            'purpose' => 'mobile_otp',
        ]);
    }

    /**
     * Decrypt the otp_token and return the embedded user_id.
     *
     * @throws OtpException if the token is tampered, expired (key rotation), or malformed.
     */
    private function resolveOtpToken(string $token): int
    {
        try {
            $payload = decrypt($token);
        } catch (\Throwable) {
            throw new OtpException(OtpException::TOKEN_INVALID);
        }

        if (
            ! is_array($payload) ||
            ($payload['purpose'] ?? '') !== 'mobile_otp' ||
            ! isset($payload['user_id'])
        ) {
            throw new OtpException(OtpException::TOKEN_INVALID);
        }

        return (int) $payload['user_id'];
    }

    /**
     * Load the user and confirm the account is eligible for OTP operations.
     *
     * @throws OtpException if the user is not found, inactive, or soft-deleted.
     */
    private function resolveActiveUser(int $userId): User
    {
        $user = User::find($userId);

        if (! $user || (int) $user->status !== 1 || $user->deleted_at !== null) {
            throw new OtpException(OtpException::INACTIVE_USER);
        }

        return $user;
    }

    /**
     * Fetch the latest pending (unused) OTP record for the given user.
     *
     * @throws OtpException if no pending record is found (session expired or already verified).
     */
    private function getPendingOtp(int $userId): MobileOtp
    {
        $record = MobileOtp::where('user_id', $userId)
            ->whereNull('used_at')
            ->latest()
            ->first();
        if (! $record) {
            throw new OtpException(OtpException::SESSION_NOT_FOUND);
        }

        return $record;
    }
}
