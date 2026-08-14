<?php

namespace App\Exports;

use App\Exports\Sheets\SalesDailyGroupedSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DailyReportExport implements WithMultipleSheets
{
    /**
     * @param  array{rows: \Illuminate\Support\Collection, totals: array<string, mixed>}|null  $rmSummary
     * @param  array{
     *     as_of: \Illuminate\Support\Carbon,
     *     brand: array{title: string, heading: string, groups: list<array<string, mixed>>},
     *     broker: array{title: string, heading: string, groups: list<array<string, mixed>>},
     *     product: array{title: string, heading: string, groups: list<array<string, mixed>>}
     * }|null  $salesPayload
     */
    public function __construct(
        protected ?array $rmSummary,
        protected ?array $salesPayload,
    ) {}

    public function sheets(): array
    {
        $sheets = [];

        if ($this->rmSummary !== null) {
            $sheets[] = new RawMaterialDailySummaryExport($this->rmSummary);
        }

        if ($this->salesPayload !== null) {
            $asOf = $this->salesPayload['as_of'];
            $sheets[] = new SalesDailyGroupedSheet($this->salesPayload['brand'], $asOf);
            $sheets[] = new SalesDailyGroupedSheet($this->salesPayload['broker'], $asOf);
            $sheets[] = new SalesDailyGroupedSheet($this->salesPayload['product'], $asOf);
        }

        return $sheets;
    }
}
