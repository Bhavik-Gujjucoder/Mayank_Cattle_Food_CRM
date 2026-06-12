<?php

namespace App\Support;

use App\Models\RawMaterialOrder;
use App\Services\RawMaterial\RawMaterialFilterService;

/** Column layout aligned with the orders listing DataTable. */
class RawMaterialOrderListExport
{
    public static function headings(bool $includeSrNo = true): array
    {
        $headings = $includeSrNo ? ['Sr No'] : [];

        return array_merge($headings, [
            'Order ID',
            'Supplier Broker',
            'Supplier',
            'Supplier Order ID',
            'Price Basis',
            'Order Date',
            'Total Qty (tons)',
            'Total Price (₹)',
            'Total Freight (₹)',
            'Status',
        ]);
    }

    /** @return list<int|string> */
    public static function row(RawMaterialOrder $order, ?int $srNo = null): array
    {
        $row = $srNo !== null ? [$srNo] : [];

        return array_merge($row, [
            $order->order_unique_id,
            $order->supplierBroker?->name ?? '—',
            $order->supplier?->name ?? '—',
            $order->supplier_order_id ?: '—',
            $order->price_basis ?: '—',
            $order->order_date?->format('d-m-Y') ?? '—',
            $order->total_qty,
            number_format((float) $order->total_price, 2),
            number_format((float) $order->total_freight, 2),
            RawMaterialFilterService::orderStatusLabel((int) $order->status),
        ]);
    }
}
