<?php

namespace App\Http\Requests\Api;

use App\Support\Api\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base form request for all mobile API endpoints.
 *
 * Converts Laravel validation failures into the standard API error shape:
 *   { success: false, message: "Validation failed.", data: { field: ["..."] } }
 * HTTP 422 Unprocessable Entity.
 */
abstract class ApiFormRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error('Validation failed.', $validator->errors(), 422)
        );
    }
}
