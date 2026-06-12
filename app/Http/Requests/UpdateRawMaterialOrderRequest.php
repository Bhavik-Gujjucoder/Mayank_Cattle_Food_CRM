<?php

namespace App\Http\Requests;

class UpdateRawMaterialOrderRequest extends StoreRawMaterialOrderRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['order_unique_id']);

        return $rules;
    }
}
