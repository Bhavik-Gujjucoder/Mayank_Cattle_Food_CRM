<?php

namespace App\Http\Requests\Api\V1\Dispatches;

use App\Http\Requests\Api\ApiFormRequest;

/**
 * Validates query-string parameters for GET /api/v1/dispatches.
 *
 * All parameters are optional. Invalid values return 422 JSON.
 *
 * Filters:
 *   dispatch_number — human-readable ID (DISP-XXXXXX) or bare integer
 *   order_number    — partial match on unique_order_id
 *   status          — payment status: unpaid | paid | partial
 *   date_from/to    — dispatch_date range (Y-m-d, inclusive)
 *   brand_id        — brand filter (broker-visible only)
 *   product_id      — product filter
 *   dealer_id       — dealer filter (broker-visible only)
 *   per_page        — pagination page size (1–100, default 15)
 */
class DispatchListRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Accepts "DISP-000042" format or bare numeric strings ("42").
            'dispatch_number' => ['nullable', 'string', 'max:20'],
            'order_number'    => ['nullable', 'string', 'max:50'],
            'status'          => ['nullable', 'string', 'in:unpaid,paid,partial'],
            'date_from'       => ['nullable', 'date'],
            'date_to'         => ['nullable', 'date', 'after_or_equal:date_from'],
            'brand_id'        => ['nullable', 'integer', 'min:1'],
            'product_id'      => ['nullable', 'integer', 'min:1'],
            'dealer_id'       => ['nullable', 'integer', 'min:1'],
            'per_page'        => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
