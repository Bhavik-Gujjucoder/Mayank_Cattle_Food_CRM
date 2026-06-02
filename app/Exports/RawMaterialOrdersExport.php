<?php

namespace App\Exports;

use App\Exports\Concerns\StyledExportHeading;
use App\Models\RawMaterialOrder;
use App\Services\RawMaterial\RawMaterialFilterService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;

class RawMaterialOrdersExport implements FromCollection, WithHeadings, WithStyles
{
    use StyledExportHeading;

    public function __construct(
        protected Collection $rows
    ) {}

    public static function fromQuery($query): self
    {
        return new self($query->get());
    }

    public function collection(): Collection
    {
        return $this->rows->map(fn (RawMaterialOrder $row) => [
            $row->order_unique_id,
            $row->supplier?->name ?? '—',
            $row->order_date?->format('d-m-Y') ?? '—',
            $row->total_qty,
            number_format((float) $row->total_price, 3),
            number_format((float) $row->total_freight, 3),
            RawMaterialFilterService::orderStatusLabel((int) $row->status),
        ]);
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Supplier',
            'Order Date',
            'Total Qty (tons)',
            'Total Price',
            'Total Freight',
            'Status',
        ];
    }
}
