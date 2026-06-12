<?php

namespace App\Exports;

use App\Exports\Concerns\StyledExportHeading;
use App\Models\RawMaterialCategory;
use App\Services\RawMaterial\RawMaterialFilterService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;

class RawMaterialCategoriesExport implements FromCollection, WithHeadings, WithStyles
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
        return $this->rows->map(fn (RawMaterialCategory $row) => [
            $row->category_unique_id,
            $row->name,
            RawMaterialFilterService::materialStatusLabel((int) $row->status),
        ]);
    }

    public function headings(): array
    {
        return ['Category ID', 'Name', 'Status'];
    }
}
