<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Mail\LoginOtpMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * Two login modes are supported based on what the user enters:
     *   • E-mail address  → Email + Password → OTP verification → dashboard
     *   • Mobile number   → Mobile + Password → role check (Dealer only) → dashboard
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $credential = trim($request->input('email')); // field is named "email" in the form

        /* ── Detect whether the credential is an e-mail or a phone number ── */
        if (filter_var($credential, FILTER_VALIDATE_EMAIL)) {
            return $this->handleEmailLogin($request, $credential);
        }

        return $this->handlePhoneLogin($request, $credential);
    }

    /* ------------------------------------------------------------------ */
    /*  EMAIL LOGIN  — password check + OTP send                          */
    /* ------------------------------------------------------------------ */
    private function handleEmailLogin(LoginRequest $request, string $email): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'min:6'],
        ]);

        $user = User::where('email', $email)->first();

        if (!$user || !Auth::validate(['email' => $email, 'password' => $request->password])) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        /* Generate and store OTP */
        $otp = rand(100000, 999999);
        $user->update([
            'otp_code'       => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        session([
            'otp_user_id' => $user->id,
            'remember_me' => $request->boolean('remember'),
        ]);

        Mail::to([$user->email, 'chandresh.gc@gmail.com', 'bhavikg.gc@gmail.com'])
            ->send(new LoginOtpMail($otp, $user));

        return redirect()->route('verify.otp.form')->with('message', 'OTP sent to your email.');
    }

    /* ------------------------------------------------------------------ */
    /*  PHONE LOGIN  — Dealer-only login with mobile number + password   */
    /* ------------------------------------------------------------------ */
    private function handlePhoneLogin(LoginRequest $request, string $phone): RedirectResponse
    {
        /* 1. Validate format and password presence */
        $request->validate([
            'email'    => ['required', 'regex:/^[0-9]{10,15}$/'],
            'password' => ['required'],
        ], [
            'email.regex'       => 'Please enter a valid email address or mobile number (10–15 digits).',
            'password.required' => 'Password is required for mobile number login.',
        ]);

        /* 2. Check if the mobile number exists in the system */
        $user = User::where('phone_no', $phone)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => 'This mobile number is not registered in the system.',
            ]);
        }

        /* 3. Only Dealers are allowed to log in with a mobile number */
        if (!$user->hasRole('dealer')) {
            throw ValidationException::withMessages([
                'email' => 'Mobile number login is only available for Dealers. Please use your email and password.',
            ]);
        }

        /* 4. Verify the password directly against the stored hash.
         *
         * Auth::validate() does a fresh DB lookup by email — it fails when the
         * dealer's email is null (email is optional on dealer accounts).
         * Hash::check() works on the user object we already have, so it is
         * reliable regardless of whether an email address is set.
         */

        Log::info('Phone login', [
    'phone' => $phone,
    'user_id' => $user->id,
    'password_hash' => $user->password,
    'check' => Hash::check($request->password, $user->password),
]);

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        /* 5. All checks passed — log the dealer in */
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
