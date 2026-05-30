<?php

namespace App\Exports;

use App\Exports\Sheets\AllOrdersSheet;
use App\Exports\Sheets\OrderItemsSheet;
use App\Exports\Sheets\OrderReceivesSheet;
use App\Models\RawMaterialOrder;
use App\Models\RawMaterialOrderItem;
use App\Models\RawMaterialReceive;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RawMaterialFullExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        $orders = RawMaterialOrder::with('supplier')->orderByDesc('id')->get();
        $items  = RawMaterialOrderItem::with(['rawMaterial', 'order'])->orderByDesc('id')->get();
        $receives = RawMaterialReceive::with(['rawMaterial', 'order'])->orderByDesc('id')->get();

        return [
            new AllOrdersSheet($orders),
            new OrderItemsSheet($items, true, false, 'All Order Items'),
            new OrderReceivesSheet($receives, true, false, 'All Receives'),
        ];
    }
}
