<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\StyledExportHeading;
use App\Models\RawMaterialOrder;
use App\Services\RawMaterial\RawMaterialFilterService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

class OrderDetailsSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    use StyledExportHeading;

    public function __construct(
        protected RawMaterialOrder $order
    ) {}

    public function title(): string
    {
        return 'Order Details';
    }

    public function headings(): array
    {
        return ['Order ID', 'Supplier Broker', 'Supplier', 'Supplier Order ID', 'Order Date', 'Price Basis', 'Total Qty (tons)', 'Total Price', 'Total Freight', 'Status'];
    }

    public function collection(): Collection
    {
        return collect([[
            $this->order->order_unique_id,
            $this->order->supplierBroker?->name ?? '—',
            $this->order->supplier?->name ?? '—',
            $this->order->supplier_order_id ?? '—',
            $this->order->order_date?->format('d-m-Y') ?? '—',
            $this->order->price_basis ?? '—',
            $this->order->total_qty,
            number_format((float) $this->order->total_price, 2),
            number_format((float) $this->order->total_freight, 2),
            RawMaterialFilterService::orderStatusLabel((int) $this->order->status),
        ]]);
    }
}
