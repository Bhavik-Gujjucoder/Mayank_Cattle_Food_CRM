<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mobile OTP Configuration
    |--------------------------------------------------------------------------
    |
    | Controls OTP expiry, verification attempt limits, and resend behaviour
    | for the Mobile API (v1). These settings apply ONLY to the mobile_otps
    | table. The web app's OTP uses separate columns on the users table and
    | is NOT affected by this file.
    |
    */

    // How long an OTP remains valid after it is generated or resent (minutes).
    'expiry_minutes' => (int) env('OTP_EXPIRY_MINUTES', 10),

    // Maximum failed verification attempts before the OTP session is locked.
    // On reaching this limit the user must log in again to get a fresh OTP.
    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),

    // Minimum wait time between consecutive resend requests (seconds).
    'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN_SECONDS', 60),

    // Maximum number of times a single OTP session can be resent.
    // After reaching this limit the user must log in again.
    'max_resend_attempts' => (int) env('OTP_MAX_RESEND_ATTEMPTS', 3),

];
