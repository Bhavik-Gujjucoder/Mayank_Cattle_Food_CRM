<?php

namespace App\Http\Requests\Api\V1\Orders;

use App\Http\Requests\Api\ApiFormRequest;

/**
 * Validates filter and pagination parameters for GET /api/v1/orders.
 *
 * All parameters are optional. Role-based restrictions on which filters
 * are honoured (e.g. brand_id is silently ignored for dealer accounts)
 * are enforced in the controller — not here. This request only ensures
 * that what is provided is syntactically valid.
 *
 * Expected query-string parameters:
 *   order_number   — partial match against unique_order_id
 *   payment_status — unpaid | paid | partial
 *   date_from      — ISO date, lower bound on order_date (inclusive)
 *   date_to        — ISO date, upper bound on order_date (inclusive)
 *   brand_id       — integer; broker-only filter (silently ignored for dealers)
 *   dealer_id      — integer; broker-only filter (silently ignored for dealers)
 *   per_page       — records per page (1–100, default 15)
 *   page           — page number (Laravel default, automatically handled)
 */
class OrderListRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true; // Role gate is enforced in the controller.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Partial string search on the order reference number.
            'order_number'   => ['nullable', 'string', 'max:50'],

            // Payment state filter — matches the enum stored on order_management.
            'payment_status' => ['nullable', 'string', 'in:unpaid,paid,partial'],

            // Date range — both are optional; when both are supplied date_to >= date_from.
            'date_from'      => ['nullable', 'date'],
            'date_to'        => ['nullable', 'date', 'after_or_equal:date_from'],

            // Broker-scoped filters — silently ignored when the caller is a dealer.
            'brand_id'       => ['nullable', 'integer', 'min:1'],
            'dealer_id'      => ['nullable', 'integer', 'min:1'],

            // Pagination controls.
            'per_page'       => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_status.in'        => 'Payment status must be one of: unpaid, paid, partial.',
            'date_to.after_or_equal'   => 'The "date to" must be on or after "date from".',
            'per_page.max'             => 'Maximum 100 records per page.',
        ];
    }
}
