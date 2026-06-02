<?php

namespace App\Http\Requests;

use App\Models\RawMaterialOrderItem;
use App\Models\RawMaterialReceive;

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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $item    = RawMaterialOrderItem::find($this->input('raw_material_order_item_id'));
            $receive = $this->route('raw_material_receive');
            if (! $item || ! $receive instanceof RawMaterialReceive) {
                return;
            }
            $maxQty = (int) $item->pending_qty + (int) $receive->qty;
            if ((int) $this->input('qty') > $maxQty) {
                $validator->errors()->add('qty', 'Quantity cannot exceed pending quantity (' . $maxQty . ' tons).');
            }
        });
    }
}
