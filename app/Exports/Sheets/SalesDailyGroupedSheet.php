<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesDailyGroupedSheet implements FromArray, WithEvents, WithTitle
{
    /** @var list<array{row: int, type: string}> */
    protected array $styleMap = [];

    /** @var list<list<string|int|float>> */
    protected array $rows = [];

    protected string $lastCol = 'G';

    protected int $colCount = 7;

    /**
     * @param  array{title: string, heading: string, groups: list<array<string, mixed>>}  $section
     */
    public function __construct(
        protected array $section,
        protected Carbon $asOf,
    ) {}

    public function title(): string
    {
        return $this->section['title'];
    }

    public function array(): array
    {
        $this->rows = [];
        $this->styleMap = [];
        $line = 1;

        $this->pushRow(['Daily Sales Sheets — ' . $this->section['heading']], $line++, 'title');
        $this->pushRow(['As of ' . $this->asOf->format('d.m.Y')], $line++, 'subtitle');
        $this->pushSpacer($line);

        if (empty($this->section['groups'])) {
            $this->pushRow(['No pending orders.'], $line++, 'empty');

            return $this->rows;
        }

        foreach ($this->section['groups'] as $group) {
            $this->pushRow([(string) $group['name']], $line++, 'group-header');
            $this->pushRow(
                ['Date', 'Party Name', 'Total', 'Rate', 'Dispatch', 'Pending', 'Last Loading'],
                $line++,
                'table-header'
            );

            foreach ($group['rows'] as $index => $row) {
                $this->pushRow([
                    $row['order_date'],
                    $row['party_name'],
                    $row['total'],
                    $row['rate'],
                    $row['dispatch'],
                    $row['pending'],
                    $row['last_loading'],
                ], $line++, $index % 2 === 0 ? 'row-odd' : 'row-even');
            }

            $this->pushRow([
                '',
                'Total',
                $group['totals']['total'],
                '',
                $group['totals']['dispatch'] ?? 0,
                $group['totals']['pending'],
                '',
            ], $line++, 'total');

            $this->pushSpacer($line, 'spacer-sm');
        }

        return $this->rows;
    }

    /**
     * @param  list<string|int|float>  $cells
     */
    protected function pushRow(array $cells, int $rowNum, string $type): void
    {
        $this->rows[] = array_pad($cells, $this->colCount, '');
        $this->styleMap[] = ['row' => $rowNum, 'type' => $type];
    }

    protected function pushSpacer(int &$line, string $type = 'spacer'): void
    {
        $this->pushRow([''], $line++, $type);
    }

    protected function fullRange(int $row): string
    {
        return 'A' . $row . ':' . $this->lastCol . $row;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach ($this->styleMap as $map) {
                    $this->applyRowStyle($sheet, $map['row'], $map['type']);
                }

                $widths = [14, 42, 12, 12, 12, 12, 16];
                foreach ($widths as $index => $width) {
                    $sheet->getColumnDimension(chr(65 + $index))->setWidth($width);
                }

                $sheet->getStyle('C:F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }

    protected function applyRowStyle(Worksheet $sheet, int $row, string $type): void
    {
        $range = $this->fullRange($row);

        if (in_array($type, ['spacer', 'spacer-sm'], true)) {
            $sheet->getRowDimension($row)->setRowHeight($type === 'spacer-sm' ? 8 : 12);

            return;
        }

        match ($type) {
            'title' => (function () use ($sheet, $range) {
                $sheet->mergeCells($range);
                $sheet->getStyle($range)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '262A2A']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
            })(),
            'subtitle' => (function () use ($sheet, $range) {
                $sheet->mergeCells($range);
                $sheet->getStyle($range)->applyFromArray([
                    'font' => ['size' => 10, 'color' => ['rgb' => '64748B']],
                ]);
            })(),
            'empty' => (function () use ($sheet, $range) {
                $sheet->mergeCells($range);
                $sheet->getStyle($range)->applyFromArray([
                    'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '64748B']],
                ]);
            })(),
            'group-header' => (function () use ($sheet, $range) {
                $sheet->mergeCells($range);
                $sheet->getStyle($range)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1E3A8A']],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'DBEAFE'],
                    ],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
            })(),
            'table-header' => $sheet->getStyle($range)->applyFromArray([
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '3E6EAF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F0F4FA'],
                ],
            ]),
            'row-odd' => $this->dataRow($sheet, $range, 'FFFFFF'),
            'row-even' => $this->dataRow($sheet, $range, 'F8FAFC'),
            'total' => $sheet->getStyle($range)->applyFromArray([
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '262A2A']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FEF3C7'],
                ],
            ]),
            default => null,
        };

        if (in_array($type, ['group-header', 'table-header', 'row-odd', 'row-even', 'total'], true)) {
            $sheet->getStyle($range)->getBorders()->getAllBorders()->applyFromArray([
                'borderStyle' => Border::BORDER_THIN,
                'color'       => ['rgb' => 'E8E8E8'],
            ]);
        }
    }

    protected function dataRow(Worksheet $sheet, string $range, string $bg): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => '334155']],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $bg],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
    }
}
