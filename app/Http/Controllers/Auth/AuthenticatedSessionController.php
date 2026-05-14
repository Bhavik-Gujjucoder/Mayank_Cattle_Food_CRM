<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Mail\LoginOtpMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

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
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        /* $request->authenticate(); */

        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
        ]);

        // Check User
        $user = User::where('email', $request->email)->first();

        // Attempt Login
        // if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
        //     throw ValidationException::withMessages([
        //         'email' => __('auth.failed'),
        //     ]);
        // }
        if (!$user || !Auth::validate([
            'email' => $request->email,
            'password' => $request->password
        ])) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }


        $otp = rand(100000, 999999);
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        // Store User ID in Session
        session([
            'otp_user_id' => $user->id,
            'remember_me' => $request->boolean('remember')
        ]);

        // dd($user->email);
        Mail::to([$user->email, 'chandresh.gc@gmail.com', 'bhavikg.gc@gmail.com'])->send(new LoginOtpMail($otp, $user));

// , 'chandresh.gc@gmail.com', 'bhavikg.gc@gmail.com'
        return redirect()->route('verify.otp.form')->with('message', 'OTP sent to your email.');

        // $request->session()->regenerate();
        // return redirect()->intended(route('dashboard', absolute: false));
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
