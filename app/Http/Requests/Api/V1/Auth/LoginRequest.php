<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\ApiFormRequest;
use Closure;

/**
 * Validates mobile login payload before authentication is attempted.
 *
 * Expected JSON body:
 *   login       — email address OR 10–15 digit phone number (auto-detected)
 *   password    — required, minimum 6 characters
 *   device_name — optional label stored on the Sanctum token (default: mobile-app)
 */
class LoginRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'login' => [
                'required',
                'string',
                'max:255',
                $this->loginFormatRule(), // Must be valid email OR phone pattern
            ],
            'password' => ['required', 'string', 'min:6'],
            'device_name' => ['nullable', 'string', 'max:255'], // Sanctum token name
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'login.required' => 'Email or phone number is required.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 6 characters.',
        ];
    }

    /**
     * Reject values that are neither a valid email nor a 10–15 digit phone number.
     */
    private function loginFormatRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $login = trim((string) $value);

            if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
                return;
            }

            if (preg_match('/^[0-9]{10,15}$/', $login)) {
                return;
            }

            $fail('The login must be a valid email address or mobile number (10–15 digits).');
        };
    }
}
