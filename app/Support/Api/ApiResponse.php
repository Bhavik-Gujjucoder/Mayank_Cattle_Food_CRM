<?php

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;

/**
 * Standardized JSON envelope for all mobile API responses.
 *
 * Success: { "success": true,  "message": "...", "data": { ... } }
 * Error:   { "success": false, "message": "...", "data": null | { errors } }
 *
 * Use success() for 2xx responses; error() for 4xx/5xx.
 * Validation errors pass field messages in data (422).
 */
class ApiResponse
{
    /**
     * @param  mixed  $data  Response payload (user object, token, errors, etc.)
     */
    public static function success(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * @param  mixed  $data  Optional detail (e.g. validation errors array)
     */
    public static function error(string $message, mixed $data = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
