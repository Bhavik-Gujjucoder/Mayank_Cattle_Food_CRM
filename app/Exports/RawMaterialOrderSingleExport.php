<?php

namespace App\Exports;

use App\Exports\Sheets\AllOrdersSheet;
use App\Exports\Sheets\OrderDetailsSheet;
use App\Exports\Sheets\OrderItemsSheet;
use App\Exports\Sheets\OrderReceivesSheet;
use App\Models\RawMaterialOrder;
use App\Models\RawMaterialOrderItem;
use App\Models\RawMaterialReceive;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RawMaterialOrderSingleExport implements WithMultipleSheets
{
    public function __construct(
        protected RawMaterialOrder $order
    ) {}

    public function sheets(): array
    {
        $order = $this->order->load(['supplier', 'items.rawMaterial', 'receives.rawMaterial']);

        return [
            new OrderDetailsSheet($order),
            new OrderItemsSheet($order->items, false, true, 'Order Items'),
            new OrderReceivesSheet($order->receives, false, true, 'Receive Entries'),
        ];
    }
}
