<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRawMaterialOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_unique_id'   => 'required|string|max:30',
            'supplier_id'       => 'required|exists:suppliers,id',
            'order_date'        => 'required|date|before_or_equal:today',
            'raw_material_id'   => 'required|array|min:1',
            'raw_material_id.*' => 'required|exists:raw_materials,id',
            'total_qty'         => 'required|array|min:1',
            'total_qty.*'       => 'required|integer|min:1',
            'price'             => 'required|array|min:1',
            'price.*'           => 'required|numeric|gt:0',
        ];
    }
}
