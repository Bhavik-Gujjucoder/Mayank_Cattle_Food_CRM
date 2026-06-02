<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\StyledExportHeading;
use App\Models\RawMaterialOrderItem;
use App\Services\RawMaterial\RawMaterialFilterService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

class OrderItemsSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    use StyledExportHeading;

    /** @param  Collection<int, RawMaterialOrderItem>  $items */
    public function __construct(
        protected Collection $items,
        protected bool $includeOrderId = false,
        protected bool $includeSrNo = true,
        protected string $title = 'Order Items'
    ) {}

    public function title(): string
    {
        return $this->title;
    }

    public function headings(): array
    {
        $headings = [];
        if ($this->includeOrderId) {
            $headings[] = 'Order ID';
        }
        if ($this->includeSrNo) {
            $headings[] = 'Sr No';
        }

        return array_merge($headings, [
            'Material',
            'Total Qty (tons)',
            'Pending Qty',
            'Received Qty',
            'Price/kg',
            'Avg Price/kg',
            'Total Price',
            'Pending Price',
            'Received Price',
            'Freight',
            'Status',
        ]);
    }

    public function collection(): Collection
    {
        return $this->items->values()->map(function (RawMaterialOrderItem $item, int $index) {
            $row = [];
            if ($this->includeOrderId) {
                $row[] = $item->order?->order_unique_id ?? '—';
            }
            if ($this->includeSrNo) {
                $row[] = $index + 1;
            }
            $row = array_merge($row, [
                $item->rawMaterial?->name ?? '—',
                $item->total_qty,
                $item->pending_qty,
                $item->received_qty,
                number_format((float) $item->price, 2),
                number_format((float) $item->price_avg, 2),
                number_format((float) $item->total_price, 2),
                number_format((float) $item->pending_price, 2),
                number_format((float) $item->received_price, 2),
                number_format((float) $item->total_freight, 2),
                RawMaterialFilterService::orderItemStatusLabel((int) $item->status),
            ]);

            return $row;
        });
    }
}
