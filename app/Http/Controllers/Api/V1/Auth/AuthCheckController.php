<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API 5 — Authentication Check (Dealer / Broker only).
 *
 * Purpose:
 *   After the mobile app receives a Sanctum Bearer token (from /auth/login or
 *   /auth/otp/verify), it calls this endpoint to confirm the token is still valid,
 *   to verify the account has an allowed role, and to fetch the full role +
 *   permissions list needed to drive mobile UI gate-keeping.
 *
 * Access policy:
 *   • Requires a valid Sanctum Bearer token — unauthenticated requests → 401.
 *   • Only Dealer and Broker role users are permitted — all others → 403.
 *   • Does NOT issue a new token; purely a read operation.
 *
 * @see \App\Http\Controllers\Api\V1\Auth\LoginController   Issues the Bearer token
 * @see \App\Http\Controllers\Api\V1\Auth\OtpController     Issues token after OTP verify (email path)
 */
class AuthCheckController extends Controller
{
    /**
     * Allowed roles for this endpoint. Values must match the `name` column
     * in Spatie's `roles` table (lowercase, as seeded).
     */
    private const ALLOWED_ROLES = ['dealer', 'broker'];

    /**
     * Verify the Bearer token and return the authenticated user's profile,
     * role, and permission list.
     *
     * Precondition: the route is protected by `auth:sanctum`, so by the time
     * this method is called, $request->user() is guaranteed to be a valid,
     * active User instance with a non-revoked token.
     *
     * HTTP responses:
     *   200 — token valid, user is a Dealer or Broker
     *   401 — token missing, invalid, or revoked (handled by Sanctum middleware)
     *   403 — token valid but user does not have Dealer or Broker role
     *
     * Success payload:
     *   {
     *     "user":        { id, name, email, phone_no, profile_picture, status },
     *     "role":        "dealer" | "broker",
     *     "permissions": ["permission-name", ...],
     *     "token_status": "valid"
     *   }
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user(); // Resolved by auth:sanctum — never null here.

        // ── Role validation ────────────────────────────────────────────────────
        // Walk through the allowed roles and find the first one this user holds.
        // If the user holds neither, deny access with 403.
        $userRole = null;
        foreach (self::ALLOWED_ROLES as $role) {
            if ($user->hasRole($role)) {
                $userRole = $role;
                break;
            }
        }

        if ($userRole === null) {
            return ApiResponse::error(
                'Access denied. This endpoint is restricted to Dealer and Broker accounts.',
                null,
                403
            );
        }

        // ── Permission retrieval ───────────────────────────────────────────────
        // getAllPermissions() returns permissions granted directly to the user
        // AND permissions inherited via their role. We pluck only the names
        // since the mobile app only needs strings for gate-keeping checks.
        $permissions = $user->getAllPermissions()
            ->pluck('name')
            ->values()
            ->all();

        // ── Profile snapshot ───────────────────────────────────────────────────
        // Keeps the response self-contained — the mobile app can cache this
        // without making a separate profile call on startup.
        $profile = [
            'id'              => $user->id,
            'name'            => $user->name,
            'email'           => $user->email,
            'phone_no'        => $user->phone_no,
            'profile_picture' => $user->profile_picture
                ? asset('storage/profile_pictures/'.$user->profile_picture)
                : null,
            'status'          => (int) $user->status,
        ];

        return ApiResponse::success('Authenticated successfully.', [
            'user'         => $profile,
            'role'         => $userRole,
            'permissions'  => $permissions,
            'token_status' => 'valid',
        ]);
    }
}
