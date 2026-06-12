<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\StyledExportHeading;
use App\Models\RawMaterialReceive;
use App\Services\RawMaterial\RawMaterialFilterService;
use App\Services\RawMaterialCacheService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

class OrderReceivesSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    use StyledExportHeading;

    /** @param  Collection<int, RawMaterialReceive>  $receives */
    public function __construct(
        protected Collection $receives,
        protected bool $includeOrderId = false,
        protected bool $includeSrNo = true,
        protected string $title = 'Receive Entries'
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
            $headings[] = 'Supplier Order ID';
        }
        if ($this->includeSrNo) {
            $headings[] = 'Sr No';
        }

        return array_merge($headings, [
            'Material',
            'Qty (tons)',
            'Freight',
            'Received Date',
            'Status',
        ]);
    }

    public function collection(): Collection
    {
        return $this->receives->values()->map(function (RawMaterialReceive $receive, int $index) {
            $row = [];
            if ($this->includeOrderId) {
                $row[] = $receive->order?->order_unique_id ?? '—';
                $row[] = $receive->order?->supplier_order_id ?? '—';
            }
            if ($this->includeSrNo) {
                $row[] = $index + 1;
            }
            $row = array_merge($row, [
                $receive->rawMaterial?->name ?? '—',
                $receive->qty,
                RawMaterialCacheService::receiveFreightPlain($receive),
                $receive->received_date?->format('d-m-Y') ?? '—',
                RawMaterialFilterService::receiveStatusLabel((int) $receive->status),
            ]);

            return $row;
        });
    }
}
