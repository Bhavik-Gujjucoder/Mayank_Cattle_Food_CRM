<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Local-only helpers for Playwright E2E (session login + OTP readback).
 * Never available outside the local environment.
 */
class E2eSupportController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $this->guard($request);

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->input('email'))->firstOrFail();

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'ok'   => true,
            'user' => [
                'id'    => $user->id,
                'email' => $user->email,
            ],
        ]);
    }

    public function latestOtp(Request $request): JsonResponse
    {
        $this->guard($request);

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->input('email'))->firstOrFail();

        return response()->json([
            'otp' => $user->otp_code,
        ]);
    }

    private function guard(Request $request): void
    {
        abort_unless(app()->environment('local'), 404);

        $secret = (string) env('E2E_DEV_SECRET', '');

        abort_if($secret === '', 503, 'E2E_DEV_SECRET is not configured.');
        abort_unless(hash_equals($secret, (string) $request->header('X-E2E-Secret')), 403);
    }
}
