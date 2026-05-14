<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Mail\LoginOtpMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class OtpController extends Controller
{
    public function verify(Request $request)
    {
        $request->validate(['otp_combined' => 'required|digits:6']);

        $user = User::find(session('otp_user_id'));

        if (!$user || (string) $user->otp_code != (string) $request->otp_combined || now()->gt($user->otp_expires_at)) {
            return back()->withErrors(['otp_combined' => 'Invalid or expired OTP.']);
        }

        $user->update(['otp_code' => null, 'otp_expires_at' => null]);

        $remember = session('remember_me', false);

        Auth::login($user, $remember);

        $request->session()->regenerate();

        session()->forget(['otp_user_id', 'remember_me']);

        // return redirect()->route('dashboard');
        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function resendOtp(Request $request)
    {
        $userId = session('otp_user_id');

        if (!$userId) {
            return redirect()->route('login')->with('error', 'Session expired. Please log in again.');
        }

        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('login')->with('error', 'User not found.');
        }

        $otp = rand(100000, 999999);
        $user->update([
            'otp_code'       => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        Mail::to([$user->email, 'chandresh.gc@gmail.com', 'bhavikg.gc@gmail.com'])->send(new LoginOtpMail($otp, $user));

        return redirect()->route('verify.otp.form')->with('message', 'OTP resent to your email.');
    }
}
