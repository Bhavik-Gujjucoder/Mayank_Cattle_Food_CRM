<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRawMaterialReceiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'raw_material_order_id'      => 'required|exists:raw_material_orders,id',
            'raw_material_order_item_id' => 'required|exists:raw_material_order_items,id',
            'qty'                        => 'required|numeric|min:0.001|decimal:0,3',
            'freight'                    => 'nullable|numeric|min:0',
            'received_date'              => 'required|date',
            'status'                     => 'required|in:0,1',
        ];
    }
}
