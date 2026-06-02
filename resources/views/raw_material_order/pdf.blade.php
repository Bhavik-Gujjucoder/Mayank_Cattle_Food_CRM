<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Purchase Order — {{ $order->order_unique_id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .muted { color: #64748b; font-size: 11px; }
        .header { margin-bottom: 20px; border-bottom: 2px solid #334155; padding-bottom: 12px; }
        .meta { width: 100%; margin-bottom: 16px; }
        .meta td { padding: 4px 8px 4px 0; vertical-align: top; }
        .meta .label { color: #64748b; width: 120px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items th { background: #e2e8f0; font-weight: bold; text-align: left; padding: 8px; border: 1px solid #cbd5e1; font-size: 11px; }
        table.items td { padding: 7px 8px; border: 1px solid #e2e8f0; font-size: 11px; }
        .text-right { text-align: right; }
        .totals { margin-top: 16px; width: 100%; }
        .totals td { padding: 4px 0; }
        .totals .label { text-align: right; padding-right: 12px; color: #64748b; }
        .totals .value { font-weight: bold; text-align: right; width: 140px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Purchase Order</h1>
        <div class="muted">Mayank Cattle Food — Raw Material</div>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Order ID</td>
            <td><strong>{{ $order->order_unique_id }}</strong></td>
            <td class="label">Order Date</td>
            <td>{{ $order->order_date?->format('d M Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Supplier</td>
            <td colspan="3">{{ $order->supplier?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td>{{ \App\Services\RawMaterial\RawMaterialFilterService::orderStatusLabel((int) $order->status) }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>#</th>
                <th>Material</th>
                <th class="text-right">Qty (tons)</th>
                <th class="text-right">Price/kg (₹)</th>
                <th class="text-right">Total Price (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->rawMaterial?->name ?? '—' }}</td>
                    <td class="text-right">{{ $item->total_qty }}</td>
                    <td class="text-right">{{ number_format($item->price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->total_price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Total Qty (tons)</td>
            <td class="value">{{ $order->total_qty }}</td>
        </tr>
        <tr>
            <td class="label">Total Price (₹)</td>
            <td class="value">{{ number_format($order->total_price, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Total Freight (₹)</td>
            <td class="value">{{ number_format($order->total_freight, 2) }}</td>
        </tr>
    </table>
</body>
</html>
