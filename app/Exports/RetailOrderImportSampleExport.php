<?php

namespace App\Exports;

use App\Exports\Sheets\ArraySheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RetailOrderImportSampleExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new ArraySheet('Orders', self::headings(), [
                ['1', '2026-11-02', 'Walk-in party', 'Brand name', 'Counter sale', 'unpaid', 'cash', '', 'Product name', '10', '820'],
                ['2', '2026-11-03', 'Second party', 'Brand name', '', 'unpaid', 'credit', '', 'Product name', '4', '640'],
            ], self::columnComments()),
            new ArraySheet('Instructions', ['Read this before you import'], [
                ['This sample is only for Retail orders. Use the Soda sample for broker and dealer orders.'],
                ['One row is one retail order. A retail order has one product.'],
                ['Total Product Order ID is only a grouping number. The app assigns the real order id.'],
                ['Party name is the buyer. Brand and Product must match an active name exactly.'],
                ['Payment status: unpaid, paid, or partial. Payment type: cash or credit.'],
                ['Credit retail can carry a late fee later on Daily Dispatch. Cash retail does not.'],
                ['Partial paid amount is required only when payment status is partial.'],
                ['Order date format: YYYY-MM-DD. Note is optional.'],
                ['The import saves nothing if any row has an error.'],
            ]),
        ];
    }

    /** @return list<string> */
    public static function headings(): array
    {
        return [
            'Total Product Order ID',
            'Order date',
            'Party name',
            'Brand',
            'Note',
            'Payment status',
            'Payment type',
            'Partial paid amount',
            'Product',
            'Qty',
            'Unit price',
        ];
    }

    /** @return array<string, string> */
    public static function columnComments(): array
    {
        return [
            'Total Product Order ID' => 'Use a new number for each retail order. This is not the order id.',
            'Order date' => 'Replace with the order date. Use YYYY-MM-DD.',
            'Party name' => 'Replace with the party who is buying. This is not a dealer.',
            'Brand' => 'Replace with the exact active brand name. The product must belong to this brand.',
            'Note' => 'Optional note for this retail order.',
            'Payment status' => 'Change to unpaid, paid, or partial.',
            'Payment type' => 'Change to cash or credit. Credit can accrue a late fee after the retail credit days.',
            'Partial paid amount' => 'Fill this only when Payment status is partial.',
            'Product' => 'Replace with the exact product name for this brand. One product per retail order.',
            'Qty' => 'Replace with the quantity. Use a whole number of at least 1.',
            'Unit price' => 'Replace with the unit price. 0 is allowed.',
        ];
    }
}
