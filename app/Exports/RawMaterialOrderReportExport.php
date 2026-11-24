<?php

namespace App\Exports;

use App\Models\RawMaterialOrder;
use App\Support\RawMaterialOrderExportRows;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RawMaterialOrderReportExport implements FromArray, WithColumnWidths, WithEvents, WithTitle
{
    /** @var list<list<int|float|string>> */
    private array $rows = [];

    /** @var list<string> */
    private array $kinds = [];

    /** @param  list<int>|null  $orderIds  Null exports every order. */
    public function __construct(
        private ?array $orderIds,
        private string $title,
    ) {}

    public function title(): string
    {
        return 'Raw Material Orders';
    }

    public function array(): array
    {
        $query = RawMaterialOrder::query()
            ->with([
                'supplier',
                'supplierBroker',
                'items.rawMaterial.category',
                'receives',
            ])
            ->orderByDesc('id');

        if ($this->orderIds !== null) {
            $query->whereIn('id', $this->orderIds);
        }

        $layout = RawMaterialOrderExportRows::excel($query->get(), $this->title);
        $this->rows = $layout['rows'];
        $this->kinds = $layout['kinds'];

        return $this->rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22,
            'B' => 22,
            'C' => 18,
            'D' => 20,
            'E' => 16,
            'F' => 16,
            'G' => 16,
            'H' => 16,
            'I' => 18,
            'J' => 16,
            'K' => 18,
            'L' => 18,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = 'L';

                $sheet->getStyle('A1:' . $lastColumn . max(1, count($this->rows)))
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                foreach ($this->kinds as $index => $kind) {
                    $row = $index + 1;
                    $end = match ($kind) {
                        'product-head', 'product' => 'J',
                        'dispatch-head', 'dispatch' => 'E',
                        default => $lastColumn,
                    };
                    $range = 'A' . $row . ':' . $end . $row;

                    if (in_array($kind, ['title', 'section'], true)) {
                        $sheet->mergeCells('A' . $row . ':' . $lastColumn . $row);
                    }

                    $style = match ($kind) {
                        'title', 'section', 'order-head' => [
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
                        'product', 'dispatch', 'empty' => [
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                        ],
                        default => null,
                    };

                    if ($style !== null) {
                        $sheet->getStyle($range)->applyFromArray($style);
                    }

                    if (in_array($kind, ['order-head', 'order-values', 'product-head', 'product', 'dispatch-head', 'dispatch', 'empty'], true)) {
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
