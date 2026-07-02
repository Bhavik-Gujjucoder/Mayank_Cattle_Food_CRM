<?php

namespace App\Services\Api\V1\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Mobile API authentication business logic.
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │ MOBILE API (this service)                                               │
 * │   login (email OR phone) + password → Sanctum Bearer token              │
 * └─────────────────────────────────────────────────────────────────────────┘
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │ WEB APP (separate — not handled here)                                   │
 * │   Email:  password OK → OTP generated & stored on user (5 min expiry)   │
 * │           → OtpController::verify → session login                       │
 * │   Phone:  password OK → session login (dealers only)                    │
 * │   OTP fields: users.otp_code, users.otp_expires_at                      │
 * │   Resend:     OtpController::resendOtp (regenerates OTP, same 5 min TTL)│
 * └─────────────────────────────────────────────────────────────────────────┘
 */
class LoginService
{
    /**
     * Attempt mobile login. Returns the User on success, null on any failure.
     *
     * Steps: resolve identifier → check account status → verify password hash.
     */
    public function authenticate(string $login, string $password): ?User
    {
        $user = $this->resolveUser(trim($login));

        if (! $this->userCanAuthenticate($user)) {
            return null;
        }

        if (! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }

    /**
     * Find user by login identifier.
     * Email format → users.email | otherwise → users.phone_no (10–15 digits).
     */
    private function resolveUser(string $login): ?User
    {
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return User::query()->where('email', $login)->first();
        }

        return User::query()->where('phone_no', $login)->first();
    }

    /**
     * Account must be active (status = 1) and not soft-deleted.
     * Mirrors web AuthenticatedSessionController::userCanAuthenticate().
     */
    private function userCanAuthenticate(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return (int) $user->status === 1 && $user->deleted_at === null;
    }
}
