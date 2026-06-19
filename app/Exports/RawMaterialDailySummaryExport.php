<?php

namespace App\Exports;

use App\Exports\Concerns\StyledExportHeading;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;

class RawMaterialDailySummaryExport implements FromCollection, WithHeadings, WithStyles
{
    use StyledExportHeading;

    /**
     * @param  array{rows: Collection<int, array<string, mixed>>, totals: array<string, mixed>}  $summary
     */
    public function __construct(
        protected array $summary
    ) {}

    public function collection(): Collection
    {
        $rows = $this->summary['rows']->values()->map(function (array $row, int $index) {
            return [
                $index + 1,
                $row['order_date'],
                $row['supplier_broker_name'],
                $row['party_name'],
                $row['material_name'],
                $row['total_qty'],
                $row['on_road_qty'],
                $row['unloading_qty'],
                $row['pending_qty'],
                number_format($row['rate'], 2, '.', ''),
                number_format($row['average'], 2, '.', ''),
                number_format($row['pending_amount'], 2, '.', ''),
                number_format($row['received_amount'], 2, '.', ''),
                number_format($row['freight'], 2, '.', ''),
            ];
        });

        $totals = $this->summary['totals'];

        return $rows->concat([
            [],
            ['', '', '', 'PENDING', '', $totals['pending']['qty'], '', '', '', '', number_format($totals['pending']['average'], 3, '.', ''), number_format($totals['pending']['amount'], 2, '.', ''), '', ''],
            ['', '', '', 'RECEIVED', '', $totals['received']['qty'], '', '', '', '', number_format($totals['received']['average'], 3, '.', ''), '', number_format($totals['received']['amount'], 2, '.', ''), ''],
            ['', '', '', 'TOTAL', '', $totals['grand']['qty'], '', '', '', '', number_format($totals['grand']['average'], 3, '.', ''), number_format($totals['pending']['amount'], 2, '.', ''), number_format($totals['received']['amount'], 2, '.', ''), ''],
        ]);
    }

    public function headings(): array
    {
        return [
            'Sr No',
            'Date',
            'Supplier Broker',
            'Party Name',
            'Material',
            'Total Qty (tons)',
            'On Road',
            'Unloading',
            'Pending',
            'Rate/kg',
            'Avg/kg',
            'Pending Amt',
            'Received Amt',
            'Freight',
        ];
    }
}
