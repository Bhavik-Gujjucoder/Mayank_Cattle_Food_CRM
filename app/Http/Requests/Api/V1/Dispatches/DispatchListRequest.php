<?php

namespace App\Http\Requests\Api\V1\Dispatches;

use App\Http\Requests\Api\ApiFormRequest;

/**
 * Validates query-string parameters for GET /api/v1/dispatches.
 *
 * All parameters are optional. Invalid values return 422 JSON.
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
            'order_number' => ['nullable', 'string', 'max:50'],
            'status'       => ['nullable', 'string', 'in:unpaid,paid,partial'],
            'date_from'    => ['nullable', 'date'],
            'date_to'      => ['nullable', 'date', 'after_or_equal:date_from'],
            'brand_id'     => ['nullable', 'integer', 'min:1'],
            'product_id'   => ['nullable', 'integer', 'min:1'],
            'dealer_id'    => ['nullable', 'integer', 'min:1'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
