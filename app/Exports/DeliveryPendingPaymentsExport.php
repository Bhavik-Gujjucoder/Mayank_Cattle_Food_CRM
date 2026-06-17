<?php

namespace App\Exports;

use App\Services\DeliveryPendingPaymentsReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DeliveryPendingPaymentsExport implements FromArray, WithStyles, WithEvents
{
    /** @var list<array{row: int, type: string}> */
    protected array $styleMap = [];

    /** @var list<list<string>> */
    protected array $rows = [];

    /** @var array<int, list<array{days: int, dispatch_date: string}>> */
    protected array $pendingDaysByRow = [];

    protected int $maxChips = 1;

    protected string $lastColLetter = 'F';

    protected int $chipStartColumn = 6;

    public function __construct(
        protected Collection $brandSections
    ) {}

    public static function fromSections(Collection $brandSections): self
    {
        return new self($brandSections);
    }

    public function array(): array
    {
        $this->rows = [];
        $this->styleMap = [];
        $this->pendingDaysByRow = [];
        $this->resolveMaxChips();

        $line = 1;

        $this->pushRow(['Sales — Dispatch Pending Payments'], $line++, 'title');

        $this->pushRow([
            'Unpaid dispatch payments after delivery — Exported ' . now()->format('d M Y, h:i A'),
        ], $line++, 'subtitle');

        $this->pushSpacer($line, 'spacer-lg');

        $brandIndex = 0;

        foreach ($this->brandSections as $section) {
            if ($brandIndex > 0) {
                $this->pushSpacer($line, 'spacer');
            }

            $this->pushRow([
                DeliveryPendingPaymentsReportService::formatBrandSectionTitle($section['brand_name']),
            ], $line++, 'brand');

            $this->pushRow(
                ['City', 'Dealer', 'Order', 'Late Fee', 'Balance Due', 'Pending Payment Days'],
                $line++,
                'header'
            );

            foreach ($section['rows'] as $row) {
                $this->pendingDaysByRow[$line] = $row['pending_days_items'] ?? [];

                $this->pushRow([
                    $row['city_name'],
                    $row['dealer_name'],
                    $row['order_label'],
                    number_format((float) ($row['total_late_fee'] ?? 0), 2),
                    number_format((float) ($row['total_balance_due'] ?? 0), 2),
                    '',
                ], $line++, 'data');
            }

            $brandIndex++;
        }

        $this->pushSpacer($line, 'spacer-lg');

        $this->pushRow([
            'Pending Payment Days: Days from dispatch date to today. Aging uses payment due days from General Settings.',
        ], $line++, 'footnote');

        $this->pushRow([
            'Late Fee: Daily accrued charge after due period (rate × qty per dispatch). Balance Due = base + late fee − partial payment.',
        ], $line++, 'footnote');

        $this->pushRow([
            'Scope: Only orders with at least one unpaid or partial dispatch payment are listed.',
        ], $line++, 'footnote');

        return $this->rows;
    }

    protected function resolveMaxChips(): void
    {
        $this->maxChips = 1;

        foreach ($this->brandSections as $section) {
            foreach ($section['rows'] as $row) {
                $this->maxChips = max($this->maxChips, count($row['pending_days_items'] ?? []));
            }
        }

        $this->maxChips = max(1, $this->maxChips);
        $this->lastColLetter = Coordinate::stringFromColumnIndex(5 + ($this->maxChips * 2));
    }

    protected function fullRowRange(int $row): string
    {
        return 'A' . $row . ':' . $this->lastColLetter . $row;
    }

    protected function pendingDaysHeaderRange(int $row): string
    {
        $start = Coordinate::stringFromColumnIndex($this->chipStartColumn);

        return $start . $row . ':' . $this->lastColLetter . $row;
    }

    /**
     * @param  list<string>  $cells
     */
    protected function pushRow(array $cells, int $rowNum, string $type): void
    {
        $this->rows[] = array_pad($cells, 6, '');
        $this->styleMap[] = ['row' => $rowNum, 'type' => $type];
    }

    protected function pushSpacer(int &$line, string $type = 'spacer'): void
    {
        $this->pushRow([''], $line++, $type);
    }

    public function styles(Worksheet $sheet): array
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach ($this->styleMap as $map) {
                    $row = $map['row'];
                    $range = $this->fullRowRange($row);
                    $type = $map['type'];

                    if (in_array($type, ['spacer', 'spacer-lg'], true)) {
                        $sheet->getRowDimension($row)->setRowHeight($type === 'spacer-lg' ? 16 : 10);
                        continue;
                    }

                    match ($type) {
                        'title' => (function () use ($sheet, $range) {
                            $sheet->mergeCells($range);
                            $style = $sheet->getStyle($range);
                            $style->getFont()->setBold(true)->setSize(14);
                            $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                        })(),
                        'subtitle', 'footnote' => (function () use ($sheet, $range, $type) {
                            $sheet->mergeCells($range);
                            $font = $sheet->getStyle($range)->getFont();
                            $font->setSize(9);
                            $font->getColor()->setRGB('64748B');
                            if ($type === 'footnote') {
                                $font->setItalic(true);
                            }
                            $sheet->getStyle($range)->getAlignment()->setWrapText(true);
                        })(),
                        'brand' => (function () use ($sheet, $range) {
                            $sheet->mergeCells($range);
                            $sheet->getStyle($range)->applyFromArray([
                                'font' => ['bold' => true, 'size' => 11],
                                'fill' => [
                                    'fillType'   => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'E2E8F0'],
                                ],
                                'alignment' => [
                                    'vertical' => Alignment::VERTICAL_CENTER,
                                ],
                            ]);
                        })(),
                        'header' => (function () use ($sheet, $range, $row) {
                            $sheet->mergeCells($this->pendingDaysHeaderRange($row));
                            $sheet->getStyle($range)->applyFromArray([
                                'font' => ['bold' => true, 'size' => 10],
                                'fill' => [
                                    'fillType'   => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F1F5F9'],
                                ],
                                'alignment' => [
                                    'vertical' => Alignment::VERTICAL_CENTER,
                                ],
                            ]);
                            $sheet->getStyle($this->pendingDaysHeaderRange($row))->getAlignment()
                                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        })(),
                        'data' => $sheet->getStyle($range)->applyFromArray([
                            'font' => ['size' => 10],
                            'alignment' => [
                                'vertical' => Alignment::VERTICAL_TOP,
                                'wrapText' => true,
                            ],
                        ]),
                        default => null,
                    };

                    if (in_array($type, ['brand', 'header', 'data'], true)) {
                        $sheet->getStyle($range)->getBorders()->getAllBorders()->applyFromArray([
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => 'E2E8F0'],
                        ]);
                    }

                    if ($type === 'data') {
                        $sheet->getRowDimension($row)->setRowHeight(38);
                    }
                }

                foreach ($this->pendingDaysByRow as $rowNum => $items) {
                    $this->applyPendingDaysChips($sheet, $rowNum, $items);
                }

                $sheet->getColumnDimension('A')->setWidth(14);
                $sheet->getColumnDimension('B')->setWidth(22);
                $sheet->getColumnDimension('C')->setWidth(22);
                $sheet->getColumnDimension('D')->setWidth(12);
                $sheet->getColumnDimension('E')->setWidth(14);

                for ($col = $this->chipStartColumn; $col <= 5 + ($this->maxChips * 2); $col++) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setWidth(6.5);
                }

                foreach ($this->styleMap as $map) {
                    if (! in_array($map['type'], ['spacer', 'spacer-lg'], true)) {
                        continue;
                    }
                    $height = $map['type'] === 'spacer-lg' ? 16 : 10;
                    $sheet->getRowDimension($map['row'])->setRowHeight($height);
                }
            },
        ];
    }

    /**
     * @param  list<array{days: int, dispatch_date: string}>  $items
     */
    protected function applyPendingDaysChips(Worksheet $sheet, int $row, array $items): void
    {
        if ($items === []) {
            $sheet->getCell(Coordinate::stringFromColumnIndex($this->chipStartColumn) . $row)->setValue('—');

            return;
        }

        foreach ($items as $index => $item) {
            $startCol = $this->chipStartColumn + ($index * 2);
            $startLetter = Coordinate::stringFromColumnIndex($startCol);
            $endLetter = Coordinate::stringFromColumnIndex($startCol + 1);
            $range = $startLetter . $row . ':' . $endLetter . $row;

            $sheet->mergeCells($range);

            $level = DeliveryPendingPaymentsReportService::dayAgingLevelFor((int) $item['days']);
            $colors = DeliveryPendingPaymentsReportService::dayAgingColors($level);

            $rich = new RichText();
            $daysRun = $rich->createTextRun((string) $item['days']);
            $daysRun->getFont()->setBold(true)->setSize(11);
            $daysRun->getFont()->getColor()->setRGB($colors['num']);

            $rich->createText("\n");

            $dateRun = $rich->createTextRun($item['dispatch_date']);
            $dateRun->getFont()->setSize(8);
            $dateRun->getFont()->getColor()->setRGB($colors['date']);

            $sheet->getCell($startLetter . $row)->setValue($rich);

            $sheet->getStyle($range)->applyFromArray([
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $colors['fill']],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => $colors['border']],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
            ]);
        }
    }
}
