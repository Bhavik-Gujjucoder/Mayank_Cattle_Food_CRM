<?php

namespace App\Http\Controllers\Api\V1\System;

use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Public system endpoints for monitoring and smoke testing.
 */
class HealthCheckController extends Controller
{
    /**
     * Confirm the mobile API is online (no authentication required).
     *
     * Use in Postman before testing auth/login to verify routing and base URL.
     *
     * Success (200): { success, message, data: { service, version, status } }
     */
    public function index(): JsonResponse
    {
        return ApiResponse::success('Mobile API is running.', [
            'service' => 'Mayank Cattle Food Mobile API',
            'version' => 'v1',
            'status' => 'ok',
        ]);
    }
}
