<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Daily Raw Material Summary — {{ $summary['summary_date']->format('d.m.Y') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #1a1a1a; }
        h1 { font-size: 14px; margin: 0 0 2px; }
        .muted { color: #64748b; font-size: 8px; margin-bottom: 10px; }
        .header { margin-bottom: 12px; border-bottom: 2px solid #334155; padding-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #e2e8f0; font-weight: bold; text-align: left; padding: 3px 2px; border: 1px solid #cbd5e1; font-size: 7px; }
        td { padding: 2px; border: 1px solid #e2e8f0; font-size: 7px; }
        .text-right { text-align: right; }
        .footer-pending td { background: #fef3c7; font-weight: bold; }
        .footer-received td { background: #ecfdf5; font-weight: bold; }
        .footer-total td { background: #e2e8f0; font-weight: bold; }
        .formula-note { color: #64748b; font-size: 8px; margin-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Daily Raw Material Summary</h1>
        <div class="muted">
            Open purchase pipeline — {{ $summary['summary_date']->format('d M Y') }}
            @if (! empty($summary['date_from']) || ! empty($summary['date_to']))
                (Order date:
                {{ $summary['date_from'] ? \Illuminate\Support\Carbon::parse($summary['date_from'])->format('d M Y') : 'Any' }}
                –
                {{ $summary['date_to'] ? \Illuminate\Support\Carbon::parse($summary['date_to'])->format('d M Y') : 'Any' }})
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Sr No</th>
                <th>Date</th>
                <th>Supplier Broker</th>
                <th>Party Name</th>
                <th>Material</th>
                <th class="text-right">Total Qty (tons)</th>
                <th class="text-right">Extra Qty</th>
                <th class="text-right">On Road</th>
                <th class="text-right">Unloading</th>
                <th class="text-right">Pending</th>
                <th class="text-right">Rate/kg</th>
                <th class="text-right">Tax (%)</th>
                <th class="text-right">Tax (₹)</th>
                <th class="text-right">Other Expense</th>
                <th class="text-right">TDS</th>
                <th class="text-right">Avg/kg</th>
                <th class="text-right">Pending Amt</th>
                <th class="text-right">Received Amt</th>
                <th class="text-right">Freight</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($summary['rows'] as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row['order_date'] }}</td>
                    <td>{{ $row['supplier_broker_name'] }}</td>
                    <td>{{ $row['party_name'] }}</td>
                    <td>{{ $row['material_name'] }}</td>
                    <td class="text-right">{{ number_format((int) $row['total_qty']) }}</td>
                    <td class="text-right">{{ number_format((int) $row['extra_qty']) }}</td>
                    <td class="text-right">{{ number_format((int) $row['on_road_qty']) }}</td>
                    <td class="text-right">{{ number_format((int) $row['unloading_qty']) }}</td>
                    <td class="text-right">{{ number_format((int) $row['pending_qty']) }}</td>
                    <td class="text-right">{{ number_format((float) $row['rate'], 2) }}</td>
                    <td class="text-right">{{ number_format((float) ($row['tax_percent'] ?? 0), 2) }}</td>
                    <td class="text-right">{{ number_format((float) ($row['tax_amount'] ?? 0), 2) }}</td>
                    <td class="text-right">{{ number_format((float) ($row['other_expense'] ?? 0), 2) }}</td>
                    <td class="text-right">{{ number_format((float) ($row['tds_amount'] ?? 0), 2) }}</td>
                    <td class="text-right">{{ number_format((float) $row['average'], 2) }}</td>
                    <td class="text-right">{{ number_format((float) $row['pending_amount'], 2) }}</td>
                    <td class="text-right">{{ number_format((float) $row['received_amount'], 2) }}</td>
                    <td class="text-right">{{ number_format((float) $row['freight'], 2) }}</td>
                </tr>
            @endforeach
            @php $totals = $summary['totals']; @endphp
            <tr class="footer-pending">
                <td colspan="5">PENDING</td>
                <td class="text-right">{{ number_format((int) $totals['pending']['qty']) }}</td>
                <td class="text-right">{{ number_format((int) ($totals['extra_qty'] ?? 0)) }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="text-right">{{ number_format((float) $totals['pending']['average'], 3) }}</td>
                <td class="text-right">{{ number_format((float) $totals['pending']['amount'], 2) }}</td>
                <td></td>
                <td></td>
            </tr>
            <tr class="footer-received">
                <td colspan="5">RECEIVED</td>
                <td class="text-right">{{ number_format((int) $totals['received']['qty']) }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="text-right">{{ number_format((float) $totals['received']['average'], 3) }}</td>
                <td></td>
                <td class="text-right">{{ number_format((float) $totals['received']['amount'], 2) }}</td>
                <td></td>
            </tr>
            <tr class="footer-total">
                <td colspan="5">TOTAL</td>
                <td class="text-right">{{ number_format((int) $totals['grand']['qty']) }}</td>
                <td class="text-right">{{ number_format((int) ($totals['extra_qty'] ?? 0)) }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="text-right">{{ number_format((float) ($totals['tax_amount'] ?? 0), 2) }}</td>
                <td class="text-right">{{ number_format((float) ($totals['other_expense'] ?? 0), 2) }}</td>
                <td class="text-right">{{ number_format((float) ($totals['tds_amount'] ?? 0), 2) }}</td>
                <td class="text-right">{{ number_format((float) $totals['grand']['average'], 3) }}</td>
                <td class="text-right">{{ number_format((float) $totals['pending']['amount'], 2) }}</td>
                <td class="text-right">{{ number_format((float) $totals['received']['amount'], 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
    <div class="formula-note">Total Price = (Qty × 1000 × Rate/kg) + Tax ₹ + Other Expense − TDS. Extra qty is taxed; Other Expense and TDS apply once on ordered qty.</div>
</body>
</html>
