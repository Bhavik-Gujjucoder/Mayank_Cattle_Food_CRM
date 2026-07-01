<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\ApiFormRequest;

/**
 * Validates the resend OTP payload for POST /api/v1/auth/otp/resend.
 *
 * Expected JSON body:
 *   otp_token — encrypted session token returned by Login or the previous Resend response.
 *               The user_id is extracted from this token server-side; no other identifier
 *               should be accepted to prevent enumeration attacks.
 */
class ResendOtpRequest extends ApiFormRequest
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
            // The encrypted session token from the Login or previous Resend response.
            'otp_token' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'otp_token.required' => 'OTP session token is required.',
        ];
    }
}
