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

class AllOrdersSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    use StyledExportHeading;

    public function __construct(
        protected Collection $orders
    ) {}

    public function title(): string
    {
        return 'All Orders';
    }

    public function headings(): array
    {
        return ['Order ID', 'Supplier', 'Order Date', 'Total Qty', 'Total Price', 'Total Freight', 'Status'];
    }

    public function collection(): Collection
    {
        return $this->orders->map(fn (RawMaterialOrder $row) => [
            $row->order_unique_id,
            $row->supplier?->name ?? '—',
            $row->order_date?->format('d-m-Y') ?? '—',
            $row->total_qty,
            number_format((float) $row->total_price, 2),
            number_format((float) $row->total_freight, 2),
            RawMaterialFilterService::orderStatusLabel((int) $row->status),
        ]);
    }
}
