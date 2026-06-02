<?php

namespace App\Exports;

use App\Exports\Concerns\StyledExportHeading;
use App\Models\RawMaterial;
use App\Services\RawMaterial\RawMaterialFilterService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;

class RawMaterialsExport implements FromCollection, WithHeadings, WithStyles
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
        return $this->rows->map(fn (RawMaterial $row) => [
            $row->raw_material_unique_id,
            $row->name,
            $row->unit,
            number_format((float) $row->total_stock, 2),
            number_format((float) $row->available_stock, 2),
            number_format((float) $row->last_purchase_price, 2),
            number_format((float) $row->average_price, 2),
            RawMaterialFilterService::materialStatusLabel((int) $row->status),
        ]);
    }

    public function headings(): array
    {
        return [
            'Material ID',
            'Name',
            'Unit',
            'Total Stock',
            'Available Stock',
            'Last Price/kg',
            'Avg Price/kg',
            'Status',
        ];
    }
}
