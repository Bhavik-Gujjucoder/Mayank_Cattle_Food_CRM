<?php

namespace App\Providers;

use App\Models\DispatchManagement;
use App\Models\RawMaterialOrderItem;
use App\Models\RawMaterialReceive;
use App\Observers\DispatchManagementObserver;
use App\Observers\RawMaterialOrderItemObserver;
use App\Observers\RawMaterialReceiveObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Mobile API login: max 5 attempts per minute per login identifier + IP.
        RateLimiter::for('api-login', function (Request $request) {
            $login = strtolower((string) $request->input('login', ''));

            return Limit::perMinute(5)->by($login.'|'.$request->ip());
        });

        // OTP verify: max 5 attempts per minute per IP.
        // Brute-force on a 6-digit OTP (1-in-900,000) is infeasible at this rate.
        RateLimiter::for('api-otp', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // OTP resend: max 3 per 10 minutes per IP — mirrors config('otp.max_resend_attempts').
        RateLimiter::for('api-otp-resend', function (Request $request) {
            return Limit::perMinutes(10, 3)->by($request->ip());
        });

        // Forgot password: max 3 per 5 minutes keyed on email + IP.
        // Keying on email prevents one address from being hammered independently of IP.
        RateLimiter::for('api-forgot-password', function (Request $request) {
            $email = strtolower((string) $request->input('email', ''));

            return Limit::perMinutes(5, 3)->by($email.'|'.$request->ip());
        });

        Gate::before(function ($user, $ability) {
            return $user->hasRole('super admin') ? true : null;
        });

        RawMaterialOrderItem::observe(RawMaterialOrderItemObserver::class);
        RawMaterialReceive::observe(RawMaterialReceiveObserver::class);
        DispatchManagement::observe(DispatchManagementObserver::class);
    }
}
