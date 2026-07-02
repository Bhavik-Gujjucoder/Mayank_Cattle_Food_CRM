<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\LoginOtpDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Web-only OTP second factor for email login.
 *
 * Not used by the mobile API (mobile uses Sanctum tokens via Api\V1\Auth\LoginController).
 *
 * Flow:
 *   1. AuthenticatedSessionController stores otp_user_id in session after password check.
 *   2. verify()   — validates 6-digit OTP against users.otp_code, checks otp_expires_at.
 *   3. resendOtp() — generates a new OTP, resets 5-minute expiry, re-queues email.
 */
class OtpController extends Controller
{
    /**
     * Verify the email OTP and complete web session login.
     *
     * OTP must match users.otp_code and not be past users.otp_expires_at (5 minutes).
     * On success: clears OTP fields, logs user in via session, forgets otp_user_id.
     */
    public function verify(Request $request)
    {
        $request->validate(['otp_combined' => 'required|digits:6']);

        $user = User::find(session('otp_user_id'));

        if (! $user || (string) $user->otp_code != (string) $request->otp_combined || now()->gt($user->otp_expires_at)) {
            return back()->withErrors(['otp_combined' => 'Invalid or expired OTP.']);
        }

        // Clear OTP from database after successful verification (single use).
        $user->update(['otp_code' => null, 'otp_expires_at' => null]);

        $remember = session('remember_me', false);

        Auth::login($user, $remember);

        $request->session()->regenerate();

        session()->forget(['otp_user_id', 'remember_me']);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Resend OTP to the user's email (web flow retry).
     *
     * Requires an active otp_user_id session from a prior password check.
     * Generates a new 6-digit code and resets expiry to now + 5 minutes.
     */
    public function resendOtp(Request $request)
    {
        $userId = session('otp_user_id');

        if (! $userId) {
            return redirect()->route('login')->with('error', 'Session expired. Please log in again.');
        }

        $user = User::find($userId);

        if (! $user) {
            return redirect()->route('login')->with('error', 'User not found.');
        }

        $otp = rand(100000, 999999);
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        LoginOtpDelivery::queue($otp, $user);

        return redirect()->route('verify.otp.form')->with(
            'message',
            'OTP is being resent to your email. It may take up to a minute to arrive.'
        );
    }
}
