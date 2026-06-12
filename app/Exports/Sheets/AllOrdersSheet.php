<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\StyledExportHeading;
use App\Support\RawMaterialOrderListExport;
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
        return RawMaterialOrderListExport::headings();
    }

    public function collection(): Collection
    {
        return $this->orders->values()->map(
            fn ($row, int $index) => RawMaterialOrderListExport::row($row, $index + 1)
        );
    }
}
