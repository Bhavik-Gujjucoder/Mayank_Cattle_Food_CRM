<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\ApiFormRequest;

/**
 * Validates the OTP verification payload for POST /api/v1/auth/otp/verify.
 *
 * Expected JSON body:
 *   otp_token   — encrypted session token returned by the Login API (email path)
 *   otp         — 6-digit numeric OTP entered by the user
 *   device_name — device label for the Sanctum token created on success
 */
class VerifyOtpRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint — identity is verified via otp_token decryption.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // The encrypted session token issued during email login.
            'otp_token'   => ['required', 'string'],

            // Must be exactly 6 numeric digits.
            'otp'         => ['required', 'string', 'digits:6'],

            // Sanctum personal access token name — stored in personal_access_tokens.name.
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'otp_token.required' => 'OTP session token is required.',
            'otp.required'       => 'OTP is required.',
            'otp.digits'         => 'OTP must be exactly 6 digits.',
        ];
    }
}
