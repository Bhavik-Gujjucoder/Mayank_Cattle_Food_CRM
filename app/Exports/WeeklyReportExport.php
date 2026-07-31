<?php

namespace App\Exports;

use App\Models\WeeklyReport;
use App\Models\WeeklyReportItem;
use App\Support\ProductUnit;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WeeklyReportExport implements FromArray, WithStyles, WithEvents
{
    /** @var list<array{row: int, type: string}> */
    protected array $styleMap = [];

    /** @var list<list<string>> */
    protected array $rows = [];

    protected string $lastCol = 'K';

    protected int $colCount = 11;

    /**
     * @param  list<array{date: Carbon, report: WeeklyReport|null}>  $days
     * @param  array{mode: string, date: string|null, date_from: string|null, date_to: string|null}  $filters
     */
    public function __construct(
        protected array $days,
        protected array $filters,
    ) {}

    public function array(): array
    {
        $this->rows = [];
        $this->styleMap = [];

        $line = 1;

        $this->pushRow(['Sales — Weekly Report (Dispatch Prediction)'], $line++, 'title');
        $this->pushRow([$this->filterLabel() . ' — Exported ' . now()->format('d M Y, h:i A')], $line++, 'subtitle');
        $this->pushSpacer($line);

        $pendingIndex = 0;

        foreach ($this->days as $day) {
            /** @var Carbon $dayDate */
            $dayDate = $day['date'];
            /** @var WeeklyReport|null $report */
            $report = $day['report'] ?? null;

            $this->pushSpacer($line);
            $this->pushRow([
                $dayDate->format('d M Y') . ' — ' . strtoupper($dayDate->format('l')),
            ], $line++, 'day-header');

            if (! $report) {
                $this->pushRow(['No report exists for this date.'], $line++, 'empty-day');
                continue;
            }

            $this->pushRow(
                ['Order No', 'Order ID', 'Product', 'Dealer', 'City', 'Qty', 'Transport', 'Truck No', 'Contact', 'Note', 'Status'],
                $line++,
                'table-header'
            );

            if ($report->items->isEmpty()) {
                $this->pushRow(['No planned orders for this date.'], $line++, 'empty-day');
            } else {
                foreach ($report->items as $item) {
                    $dealer = $item->order?->dealer;
                    $isConfirmed = $item->isConfirmed();
                    $type = $isConfirmed
                        ? 'row-confirmed'
                        : ($pendingIndex % 2 === 0 ? 'row-pending-odd' : 'row-pending-even');
                    $pendingIndex++;

                    $this->pushRow([
                        (string) $item->sort_order,
                        $item->order?->unique_order_id ?? '—',
                        $item->product?->name ?? '—',
                        $dealer?->user?->name ?? $dealer?->firm_shop_name ?? '—',
                        $dealer?->city?->city_name ?? '—',
                        ProductUnit::formatWithUnit($item->quantity, $item->product?->unit),
                        $item->transporter?->name ?? '—',
                        $item->truck_number ?? '—',
                        $item->driver_contact ?? '—',
                        $item->note ?: '—',
                        $isConfirmed ? 'Confirmed' : 'Pending',
                    ], $line++, $type);
                }
            }

            $this->pushSpacer($line, 'spacer-sm');
            $this->pushRow(['Production summary'], $line++, 'summary-header');
            $this->pushRow(
                ['Total quantity (bags)', number_format($report->totalQuantityInBags(), 2)],
                $line++,
                'summary-row'
            );
            $this->pushRow(
                ['Already produced / ready stock', number_format((float) $report->already_produced, 2)],
                $line++,
                'summary-row'
            );
            $this->pushRow(
                ['Difference', number_format($report->differenceInBags(), 2)],
                $line++,
                'summary-row'
            );
            $this->pushRow(
                ['Bags per hour (divisor)', number_format($report->bagsPerHour(), 2)],
                $line++,
                'summary-row'
            );
            $this->pushRow(
                ['Production hours (÷ ' . number_format($report->bagsPerHour(), 0) . ')', number_format($report->productionHours(), 2)],
                $line++,
                'summary-row'
            );
        }

        $this->pushSpacer($line);
        $this->pushRow(['Pending rows: white/light grey with blue accent. Confirmed rows: green background.'], $line++, 'footnote');
        $this->pushRow(['Production summary: difference = total quantity minus ready stock.'], $line++, 'footnote');

        return $this->rows;
    }

    protected function filterLabel(): string
    {
        if (($this->filters['mode'] ?? 'single') === 'range') {
            $from = Carbon::parse($this->filters['date_from'])->format('d M Y');
            $to = Carbon::parse($this->filters['date_to'])->format('d M Y');

            return "Date range: {$from} – {$to} (" . count($this->days) . ' day(s))';
        }

        $date = Carbon::parse($this->filters['date'] ?? now())->format('d M Y');

        return 'Date: ' . $date;
    }

    /**
     * @param  list<string>  $cells
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
                    $type = $map['type'];
                    $range = $this->fullRange($row);

                    if (in_array($type, ['spacer', 'spacer-sm'], true)) {
                        $sheet->getRowDimension($row)->setRowHeight($type === 'spacer-sm' ? 6 : 12);
                        continue;
                    }

                    match ($type) {
                        'title' => (function () use ($sheet, $range) {
                            $sheet->mergeCells($range);
                            $sheet->getStyle($range)->applyFromArray([
                                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '262A2A']],
                                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                            ]);
                        })(),
                        'subtitle', 'footnote' => (function () use ($sheet, $range, $type) {
                            $sheet->mergeCells($range);
                            $style = $sheet->getStyle($range);
                            $style->getFont()->setSize(9)->getColor()->setRGB('64748B');
                            if ($type === 'footnote') {
                                $style->getFont()->setItalic(true);
                            }
                            $style->getAlignment()->setWrapText(true);
                        })(),
                        'day-header' => $sheet->getStyle($range)->applyFromArray([
                            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '3E6EAF']],
                            'fill' => [
                                'fillType'   => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F0F4FA'],
                            ],
                            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                        ]),
                        'table-header' => $sheet->getStyle($range)->applyFromArray([
                            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '3E6EAF']],
                            'fill' => [
                                'fillType'   => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F0F4FA'],
                            ],
                            'alignment' => [
                                'vertical' => Alignment::VERTICAL_CENTER,
                                'wrapText'   => true,
                            ],
                        ]),
                        'row-pending-odd' => $this->applyDataRowStyle($sheet, $range, 'FFFFFF', '475569'),
                        'row-pending-even' => $this->applyDataRowStyle($sheet, $range, 'F8FAFC', '475569'),
                        'row-confirmed' => $this->applyDataRowStyle($sheet, $range, 'ECFDF5', '166534'),
                        'empty-day' => (function () use ($sheet, $range) {
                            $sheet->mergeCells($range);
                            $sheet->getStyle($range)->applyFromArray([
                                'font' => ['size' => 10, 'italic' => true, 'color' => ['rgb' => '64748B']],
                                'fill' => [
                                    'fillType'   => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F8FAFC'],
                                ],
                            ]);
                        })(),
                        'summary-header' => (function () use ($sheet, $range, $row) {
                            $sheet->mergeCells('A' . $row . ':B' . $row);
                            $sheet->getStyle($range)->applyFromArray([
                                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '3E6EAF']],
                                'fill' => [
                                    'fillType'   => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'EFF6FF'],
                                ],
                            ]);
                        })(),
                        'summary-row' => (function () use ($sheet, $range, $row) {
                            $sheet->getStyle('A' . $row)->applyFromArray([
                                'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '475569']],
                            ]);
                            $sheet->getStyle('B' . $row)->applyFromArray([
                                'font' => ['size' => 10, 'color' => ['rgb' => '262A2A']],
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                            ]);
                        })(),
                        default => null,
                    };

                    if (in_array($type, ['day-header', 'table-header', 'row-pending-odd', 'row-pending-even', 'row-confirmed', 'empty-day', 'summary-header'], true)) {
                        $sheet->getStyle($range)->getBorders()->getAllBorders()->applyFromArray([
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => 'E8E8E8'],
                        ]);
                    }

                    if (str_starts_with($type, 'row-')) {
                        $statusCell = 'K' . $row;
                        $status = $sheet->getCell($statusCell)->getValue();
                        if ($status === WeeklyReportItem::STATUS_CONFIRMED || $status === 'Confirmed') {
                            $sheet->getStyle($statusCell)->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['rgb' => '047857']],
                                'fill' => [
                                    'fillType'   => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'D1FAE5'],
                                ],
                            ]);
                        } elseif ($status === WeeklyReportItem::STATUS_PENDING || $status === 'Pending') {
                            $sheet->getStyle($statusCell)->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['rgb' => 'B45309']],
                                'fill' => [
                                    'fillType'   => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'FFEECD'],
                                ],
                            ]);
                        }
                    }

                    if (in_array($type, ['row-pending-odd', 'row-pending-even', 'row-confirmed'], true)) {
                        $sheet->getRowDimension($row)->setRowHeight(-1);
                    }
                }

                $widths = [10, 18, 22, 22, 14, 12, 18, 14, 14, 28, 12];
                foreach ($widths as $index => $width) {
                    $sheet->getColumnDimension(chr(65 + $index))->setWidth($width);
                }
            },
        ];
    }

    protected function applyDataRowStyle(Worksheet $sheet, string $range, string $bg, string $text): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => $text]],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $bg],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
        ]);
    }
}
