<?php

namespace App\Http\Requests;

class UpdateRawMaterialReceiveRequest extends StoreRawMaterialReceiveRequest
{
    public function rules(): array
    {
        return [
            'raw_material_order_id'      => 'required|exists:raw_material_orders,id',
            'raw_material_order_item_id' => 'required|exists:raw_material_order_items,id',
            'qty'                        => 'required|integer|min:1',
            'freight'                    => 'nullable|numeric|min:0',
            'received_date'              => 'required|date',
            'status'                     => 'required|in:0',
        ];
    }
}
