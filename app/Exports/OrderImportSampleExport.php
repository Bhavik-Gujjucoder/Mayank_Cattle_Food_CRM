<?php

namespace App\Exports;

use App\Exports\Sheets\ArraySheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class OrderImportSampleExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new ArraySheet('Orders', self::headings(), [
                ['1', '2026-11-02', 'Broker name', 'Brand name', 'DEALER-CODE', 'Shop address, city', 'unpaid', 'credit', '', 'Product name', '50', '820'],
                ['1', '2026-11-02', 'Broker name', 'Brand name', 'DEALER-CODE', 'Shop address, city', 'unpaid', 'credit', '', 'Second product', '20', '640'],
                ['2', '2026-11-03', 'Broker name', 'Brand name', 'DEALER-CODE-2', 'Another address', 'partial', 'cash', '5000', 'Product name', '10', '820'],
            ], self::columnComments()),
            new ArraySheet('Instructions', ['Read this before you import'], [
                ['Hover a heading on the Orders sheet. The comment on that column says what to type there.'],
                ['Replace the example rows on the Orders sheet. Do not rename the column headings.'],
                ['One row is one product line. Rows with the same Order group become one soda/order.'],
                ['Order group is only a grouping number (1, 2, 3). The app assigns the real order id.'],
                ['Broker, Brand, and Product must match an active name exactly.'],
                ['Dealer code must match the dealer code, and that dealer must belong to the broker and brand on the row.'],
                ['Payment status: unpaid, paid, or partial. Payment type: cash or credit.'],
                ['Partial paid amount is required only when payment status is partial.'],
                ['Qty is a whole number of at least 1. Unit price can be 0.'],
                ['Order date format: YYYY-MM-DD.'],
                ['Header columns (date, broker, brand, dealer, address, payment) must be the same on every row of one Order group.'],
                ['The import saves nothing if any row has an error.'],
            ]),
        ];
    }

    /** @return list<string> */
    public static function headings(): array
    {
        return [
            'Order group',
            'Order date',
            'Broker',
            'Brand',
            'Dealer code',
            'Delivery address',
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
            'Order group' => 'Change this number when a new order starts. Use the same number on every product line of one order. This is not the order id.',
            'Order date' => 'Replace with the order date. Use YYYY-MM-DD. Keep the same date on every row of this order group.',
            'Broker' => 'Replace with the exact active broker name. Keep the same broker on every row of this order group.',
            'Brand' => 'Replace with the exact active brand name. The product must belong to this brand.',
            'Dealer code' => 'Replace DEALER-CODE with the dealer code. That dealer must belong to the broker and brand on this row.',
            'Delivery address' => 'Replace with the delivery address for this order.',
            'Payment status' => 'Change to unpaid, paid, or partial.',
            'Payment type' => 'Change to cash or credit.',
            'Partial paid amount' => 'Fill this only when Payment status is partial. Leave it blank for unpaid or paid.',
            'Product' => 'Replace with the exact product name for this brand. The same product name can be used again on this order group or on another order group.',
            'Qty' => 'Replace with the quantity. Use a whole number of at least 1.',
            'Unit price' => 'Replace with the unit price for this product. 0 is allowed.',
        ];
    }
}
