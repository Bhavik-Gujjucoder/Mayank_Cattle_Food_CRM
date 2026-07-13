<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Logout — revoke the current Sanctum Bearer token.
 *
 * Purpose:
 *   When the mobile app signs out, it calls this endpoint so the server
 *   invalidates the token used for the request. Subsequent requests with
 *   the same token receive 401.
 *
 * Access policy:
 *   • Requires a valid Sanctum Bearer token — unauthenticated requests → 401.
 *   • Only the current token is revoked; other device tokens remain valid.
 *
 * @see \App\Http\Controllers\Api\V1\Auth\LoginController  Issues the Bearer token
 * @see \App\Http\Controllers\Api\V1\Auth\OtpController     Issues token after OTP verify (email path)
 */
class LogoutController extends Controller
{
    /**
     * Revoke the Bearer token used for this request.
     *
     * HTTP responses:
     *   200 — token revoked successfully
     *   401 — token missing, invalid, or already revoked (handled by Sanctum middleware)
     */
    public function store(Request $request): JsonResponse
    {
        $accessToken = $request->user()->currentAccessToken();

        if ($accessToken instanceof PersonalAccessToken) {
            $accessToken->delete();
        } elseif ($plainTextToken = $request->bearerToken()) {
            // Stateful Sanctum requests may attach a TransientToken while the
            // client still sends the original Bearer string — revoke via lookup.
            PersonalAccessToken::findToken($plainTextToken)?->delete();
        }

        Auth::guard('sanctum')->forgetUser();
        Auth::guard('web')->logout();

        return ApiResponse::success('Logged out successfully.');
    }
}
