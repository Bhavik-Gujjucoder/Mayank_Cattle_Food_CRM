<?php

namespace App\Exports;

use App\Exports\Concerns\StyledExportHeading;
use App\Models\RawMaterialReceive;
use App\Services\RawMaterial\RawMaterialFilterService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;

class RawMaterialReceivesExport implements FromCollection, WithHeadings, WithStyles
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
        return $this->rows->values()->map(function (RawMaterialReceive $row, int $index) {
            return [
                $index + 1,
                $row->order?->order_unique_id ?? '—',
                $row->rawMaterial?->name ?? '—',
                $row->qty,
                number_format((float) $row->freight, 2),
                $row->received_date?->format('d-m-Y') ?? '—',
                RawMaterialFilterService::receiveStatusLabel((int) $row->status),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Sr No',
            'Order ID',
            'Material',
            'Qty (tons)',
            'Freight',
            'Received Date',
            'Status',
        ];
    }
}
