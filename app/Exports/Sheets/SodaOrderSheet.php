<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\StyledExportHeading;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

class SodaOrderSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    use StyledExportHeading;

    /**
     * @param  list<string>  $headings
     * @param  list<list<int|string|null>>  $rows
     */
    public function __construct(
        private string $title,
        private array $headings,
        private array $rows,
    ) {}

    public function title(): string
    {
        return $this->title;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function collection(): Collection
    {
        return collect($this->rows);
    }
}
