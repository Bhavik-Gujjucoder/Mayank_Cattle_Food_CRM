<?php

namespace App\Exports;

use App\Exports\Concerns\StyledExportHeading;
use App\Support\RawMaterialOrderListExport;
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
        return new self($query->with(['supplier', 'supplierBroker'])->get());
    }

    public function collection(): Collection
    {
        return $this->rows->values()->map(
            fn ($row, int $index) => RawMaterialOrderListExport::row($row, $index + 1)
        );
    }

    public function headings(): array
    {
        return RawMaterialOrderListExport::headings();
    }
}
