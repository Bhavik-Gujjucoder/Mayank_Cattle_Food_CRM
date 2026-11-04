<?php

namespace App\Exports;

use App\Exports\Sheets\ArraySheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RawMaterialOrderImportSampleExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new ArraySheet('Orders', self::headings(), [
                ['1', '2026-11-02', 'Supplier broker name', 'Supplier name', 'SUP-100', 'FOR + GST', '10000', 'Raw material name', '12.500', '28.50', '5', '0', '0'],
                ['1', '2026-11-02', 'Supplier broker name', 'Supplier name', 'SUP-100', 'FOR + GST', '10000', 'Second material', '4', '31', '5', '250', '100'],
                ['2', '2026-11-02', 'Supplier broker name', 'Supplier name', '', 'Ex-Factory + GST', '0', 'Raw material name', '1.250', '30', '0', '0', '0'],
            ], self::columnComments()),
            new ArraySheet('Instructions', ['Read this before you import'], [
                ['Hover a heading on the Orders sheet. The comment on that column says what to type there.'],
                ['Replace the example rows on the Orders sheet. Do not rename the column headings.'],
                ['One row is one material line. Rows with the same Total Product Order ID become one purchase order.'],
                ['Total Product Order ID is only a grouping number. The app assigns the real order id (RMO/year/number).'],
                ['Supplier broker, Supplier, and Raw material must match an active name exactly.'],
                ['The supplier must belong to the supplier broker on that row.'],
                ['Price basis must be exactly: FOR + GST  or  Ex-Factory + GST.'],
                ['Order date can be today, a past date, or a future date. Format: YYYY-MM-DD.'],
                ['Qty is in tons, up to 3 decimal places, at least 0.001. Price must be greater than 0.'],
                ['Tax percent is 0 to 100. Other expense, TDS amount, and Advance payment can be 0 or blank.'],
                ['Advance payment, supplier order id, date, broker, supplier, and price basis must be the same on every row of one Total Product Order ID.'],
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
            'Supplier broker',
            'Supplier',
            'Supplier order id',
            'Price basis',
            'Advance payment',
            'Raw material',
            'Qty',
            'Price',
            'Tax percent',
            'Other expense',
            'TDS amount',
        ];
    }

    /** @return array<string, string> */
    public static function columnComments(): array
    {
        return [
            'Total Product Order ID' => 'Change this number when a new order starts. Use the same number on every material line of one order. This is not the order id.',
            'Order date' => 'Replace with the order date, YYYY-MM-DD. A future date is allowed. Keep the same date on every row of this Total Product Order ID.',
            'Supplier broker' => 'Replace with the exact active supplier broker name.',
            'Supplier' => 'Replace with the exact active supplier name. The supplier must belong to the supplier broker on this row.',
            'Supplier order id' => 'Replace with the supplier’s own order number, or leave blank. Keep it the same on every row of this Total Product Order ID.',
            'Price basis' => 'Change to exactly FOR + GST or Ex-Factory + GST.',
            'Advance payment' => 'Replace with the advance amount for the whole order. Use 0 if there is none. Keep the same amount on every row of this Total Product Order ID.',
            'Raw material' => 'Replace with the exact active raw material name. The same material name can be used again on this Total Product Order ID or on another one.',
            'Qty' => 'Replace with the quantity in tons. At least 0.001, up to 3 decimal places.',
            'Price' => 'Replace with the price per kg. It must be greater than 0.',
            'Tax percent' => 'Replace with the tax percent from 0 to 100, or leave blank for 0.',
            'Other expense' => 'Replace with any other expense, or leave blank for 0.',
            'TDS amount' => 'Replace with the TDS amount, or leave blank for 0.',
        ];
    }
}
