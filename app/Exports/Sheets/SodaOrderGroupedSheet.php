<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SodaOrderGroupedSheet implements FromCollection, WithStyles, WithTitle
{
    /**
     * @param  list<list<int|string>>  $rows
     */
    public function __construct(
        private array $rows,
    ) {}

    public function title(): string
    {
        return 'Order Detail';
    }

    public function collection(): Collection
    {
        return collect($this->rows);
    }

    public function styles(Worksheet $sheet): array
    {
        $styles = [];

        foreach ($this->rows as $index => $row) {
            $excelRow = $index + 1;
            $marker = (string) ($row[0] ?? '');

            if ($marker === 'Order') {
                $styles[$excelRow] = [
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '3E6EAF'],
                    ],
                ];
            }

            if ($marker === 'Product') {
                $styles[$excelRow] = [
                    'font' => ['bold' => true, 'color' => ['rgb' => '014E9C']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D6E4F5'],
                    ],
                ];
            }

            if ($marker === 'Dispatches') {
                $styles[$excelRow] = [
                    'font' => ['bold' => true, 'color' => ['rgb' => '3E6EAF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EEF3F9'],
                    ],
                ];
            }
        }

        return $styles;
    }
}
