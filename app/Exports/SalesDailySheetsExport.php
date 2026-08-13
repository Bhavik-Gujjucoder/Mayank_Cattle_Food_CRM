<?php

namespace App\Exports;

use App\Exports\Sheets\SalesDailyGroupedSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SalesDailySheetsExport implements WithMultipleSheets
{
    /**
     * @param  array{
     *     as_of: \Illuminate\Support\Carbon,
     *     brand: array{title: string, heading: string, groups: list<array<string, mixed>>},
     *     broker: array{title: string, heading: string, groups: list<array<string, mixed>>},
     *     product: array{title: string, heading: string, groups: list<array<string, mixed>>}
     * }  $payload
     */
    public function __construct(
        protected array $payload
    ) {}

    public function sheets(): array
    {
        $asOf = $this->payload['as_of'];

        return [
            new SalesDailyGroupedSheet($this->payload['brand'], $asOf),
            new SalesDailyGroupedSheet($this->payload['broker'], $asOf),
            new SalesDailyGroupedSheet($this->payload['product'], $asOf),
        ];
    }
}
