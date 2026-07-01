<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\ApiFormRequest;

/**
 * Validates the forgot-password payload for POST /api/v1/auth/forgot-password.
 *
 * Only the email field is required here. Account existence and status are
 * checked in the controller so we can control the response precisely.
 *
 * Expected JSON body:
 *   email — the user's registered email address
 */
class ForgotPasswordRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint — no authentication required.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Must be a syntactically valid email; existence checked in the controller.
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required.',
            'email.email'    => 'Please provide a valid email address.',
        ];
    }
}
