<?php

use App\Http\Controllers\Api\V1\Auth\AuthCheckController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\OtpController;
use App\Http\Controllers\Api\V1\Dispatches\DispatchListingController;
use App\Http\Controllers\Api\V1\Orders\OrderController;
use App\Http\Controllers\Api\V1\System\HealthCheckController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API Routes (v1)
|--------------------------------------------------------------------------
|
| Base URL: /api/v1
|
| Authentication model (mobile vs web):
|   • Web app  — Email login: password → session OTP → session auth.
|                Phone login (dealers): password → session auth.
|   • Mobile API — Email login:  password → OTP email → verify → Sanctum token.
|                  Phone login:  password → Sanctum token (immediate).
|
| Prefix reference:
|   auth/*          — Public authentication endpoints (login, otp, forgot-password)
|   system/*        — Connectivity / health checks (no auth required)
|   profile/*       — Authenticated user profile (future, auth:sanctum)
|   notifications/* — Push / in-app notifications (future)
|   training/*      — Training module APIs (future)
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    /*
    |----------------------------------------------------------------------
    | Authentication (public)
    |----------------------------------------------------------------------
    | POST auth/login            — Validate credentials.
    |                              Email → 202 + otp_token (OTP flow).
    |                              Phone → 200 + Sanctum Bearer token.
    |
    | POST auth/otp/verify       — Submit OTP to complete email login → Bearer token.
    | POST auth/otp/resend       — Regenerate & resend OTP; returns new otp_token.
    |
    | POST auth/forgot-password  — Send a password reset link to the user's email.
    */
    Route::prefix('auth')->name('auth.')->group(function () {

        Route::post('login', [LoginController::class, 'store'])
            ->middleware('throttle:api-login')
            ->name('login');

        // OTP endpoints — used only during the email login two-step flow.
        Route::prefix('otp')->name('otp.')->group(function () {

            Route::post('verify', [OtpController::class, 'verify'])
                ->middleware('throttle:api-otp')
                ->name('verify');

            Route::post('resend', [OtpController::class, 'resend'])
                ->middleware('throttle:api-otp-resend')
                ->name('resend');
        });

        // Forgot password — generate and email a password reset link.
        Route::post('forgot-password', [ForgotPasswordController::class, 'store'])
            ->middleware('throttle:api-forgot-password')
            ->name('forgot-password');
    });

    /*
    |----------------------------------------------------------------------
    | System (public)
    |----------------------------------------------------------------------
    | GET system/health-check — Confirms the API is reachable (Postman smoke test).
    */
    Route::prefix('system')->name('system.')->group(function () {
        Route::get('health-check', [HealthCheckController::class, 'index'])
            ->name('health-check');
    });

    /*
    |----------------------------------------------------------------------
    | Protected routes (Bearer token required)
    |----------------------------------------------------------------------
    | All routes here require: Authorization: Bearer {sanctum_token}
    |
    | GET  auth/me     — Verify token; return role + permissions (Dealer/Broker only).
    | POST auth/logout — Revoke the current Bearer token.
    | GET  orders      — Paginated, filterable order list (Dealer/Broker only).
    */
    Route::middleware('auth:sanctum')->group(function () {

        Route::prefix('auth')->name('auth.')->group(function () {
            // Dealer / Broker authentication check — validates token + role.
            Route::get('me', [AuthCheckController::class, 'show'])
                ->name('me');

            Route::post('logout', [LogoutController::class, 'store'])
                ->name('logout');
        });

        // Soda/Order listing — Dealer and Broker roles only.
        Route::get('orders', [OrderController::class, 'index'])
            ->name('orders.index');

        /*
         * Dispatch Listing (API 10) — Dedicated, standalone dispatch listing.
         *
         * Dealer  → sees only dispatches tied to their own orders.
         * Broker  → sees only dispatches tied to orders where broker_id = user.
         * Others  → 403 Access Denied (enforced inside the controller).
         *
         * Supported filters: dispatch_number, order_number, status,
         *                    date_from, date_to, brand_id, product_id,
         *                    dealer_id, per_page.
         */
        Route::get('dispatches', [DispatchListingController::class, 'list'])
            ->name('dispatches.list');

        // Route::prefix('profile')->name('profile.')->group(function () { ... });
        // Route::prefix('notifications')->name('notifications.')->group(function () { ... });
        // Route::prefix('training')->name('training.')->group(function () { ... });
    });
});
