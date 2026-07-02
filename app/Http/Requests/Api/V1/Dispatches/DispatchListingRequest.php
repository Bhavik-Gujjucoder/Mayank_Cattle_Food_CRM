<?php

namespace App\Http\Requests\Api\V1\Dispatches;

use App\Http\Requests\Api\ApiFormRequest;

/**
 * Validates all query-string parameters for the Dispatch Listing API.
 *
 * Endpoint: GET /api/v1/dispatches
 * Controller: DispatchListingController::list()
 *
 * All parameters are optional. A missing parameter means "no filter applied".
 * Invalid values return HTTP 422 with field-level errors in the `data` key
 * (see ApiFormRequest::failedValidation for the envelope format).
 *
 * ── Available filters ─────────────────────────────────────────────────────
 *
 *   dispatch_number  Exact dispatch lookup by human-readable reference.
 *                    Accepts: "DISP-000042", "DISP-42", or bare "42".
 *                    Max 20 characters to prevent oversized input.
 *
 *   order_number     Partial match on the parent order's unique_order_id.
 *                    Max 50 characters.
 *
 *   status           Payment status label: unpaid | paid | partial.
 *                    Mapped to DB integers (0/1/2) in the controller.
 *
 *   date_from        Lower bound for dispatch_date (Y-m-d, inclusive).
 *   date_to          Upper bound for dispatch_date (Y-m-d, inclusive).
 *                    Validated: date_to must be on or after date_from.
 *
 *   brand_id         Filter by brand (integer ≥ 1).
 *                    Silently ignored for Dealer role (controller-level).
 *
 *   product_id       Filter by dispatched product (integer ≥ 1).
 *
 *   dealer_id        Filter by dealer (integer ≥ 1).
 *                    Silently ignored for Dealer role (controller-level).
 *
 *   per_page         Records per page. Integer between 1 and 100.
 *                    Defaults to 15 in the controller.
 *
 * ── Filters NOT supported (no DB columns in this application) ─────────────
 *   supplier, delivery_status, invoice, city, state
 */
class DispatchListingRequest extends ApiFormRequest
{
    /**
     * All authenticated users reaching this request have already passed the
     * Sanctum middleware. Role-based access (Dealer / Broker only) is enforced
     * in the controller so that the 403 response comes through ApiResponse
     * rather than a raw abort.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
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

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in'               => 'Status must be one of: unpaid, paid, partial.',
            'date_to.after_or_equal'  => 'The end date must be on or after the start date.',
            'per_page.max'            => 'Per page may not exceed 100.',
            'brand_id.integer'        => 'Brand ID must be a valid integer.',
            'product_id.integer'      => 'Product ID must be a valid integer.',
            'dealer_id.integer'       => 'Dealer ID must be a valid integer.',
        ];
    }
}
