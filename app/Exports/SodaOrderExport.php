<?php

namespace App\Exports;

use App\Models\OrderManagement;
use App\Support\SodaOrderExportRows;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SodaOrderExport implements FromArray, WithColumnWidths, WithEvents, WithTitle
{
    /** @var list<list<int|string>> */
    private array $rows;

    /** @var list<string> */
    private array $kinds;

    /**
     * @param  array{ordered: int, dispatched: int, pending: int}  $summary
     * @param  Collection<int, OrderManagement>  $orders
     */
    public function __construct(
        array $summary,
        Collection $orders,
        private string $title,
    ) {
        $layout = SodaOrderExportRows::excel($summary, $orders, $title);
        $this->rows = $layout['rows'];
        $this->kinds = $layout['kinds'];
    }

    public function title(): string
    {
        return 'Soda Orders';
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 28,
            'B' => 18,
            'C' => 16,
            'D' => 24,
            'E' => 16,
            'F' => 18,
            'G' => 16,
            'H' => 16,
            'I' => 16,
            'J' => 18,
            'K' => 18,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = 'K';

                $sheet->getStyle('A1:' . $lastColumn . max(1, count($this->rows)))
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                foreach ($this->kinds as $index => $kind) {
                    $row = $index + 1;
                    $end = match ($kind) {
                        'cards-head', 'cards' => 'B',
                        'product-head', 'product' => 'F',
                        default => $lastColumn,
                    };
                    $range = 'A' . $row . ':' . $end . $row;

                    if (in_array($kind, ['title', 'section'], true)) {
                        $sheet->mergeCells('A' . $row . ':' . $lastColumn . $row);
                    }

                    $style = match ($kind) {
                        'title', 'section', 'order-head', 'cards-head' => [
                            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => $kind === 'title' ? 16 : 11],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3E6EAF']],
                        ],
                        'subtitle' => [
                            'font' => ['italic' => true, 'color' => ['rgb' => '3E6EAF']],
                        ],
                        'order-values' => [
                            'font' => ['bold' => true, 'color' => ['rgb' => '014E9C']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F4F7FB']],
                        ],
                        'product-head' => [
                            'font' => ['bold' => true, 'color' => ['rgb' => '014E9C']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D6E4F5']],
                        ],
                        'dispatch-head' => [
                            'font' => ['bold' => true, 'color' => ['rgb' => '3E6EAF']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF3F9']],
                        ],
                        'cards', 'product', 'dispatch', 'empty' => [
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                        ],
                        default => null,
                    };

                    if ($style !== null) {
                        $sheet->getStyle($range)->applyFromArray($style);
                    }

                    if (in_array($kind, ['cards-head', 'cards', 'order-head', 'order-values', 'product-head', 'product', 'dispatch-head', 'dispatch', 'empty'], true)) {
                        $sheet->getStyle($range)
                            ->getBorders()
                            ->getAllBorders()
                            ->setBorderStyle(Border::BORDER_THIN)
                            ->getColor()
                            ->setRGB('D6E4F5');
                    }

                    if (in_array($kind, ['title', 'section', 'order-head'], true)) {
                        $sheet->getRowDimension($row)->setRowHeight($kind === 'title' ? 28 : 22);
                    }
                }
            },
        ];
    }
}
