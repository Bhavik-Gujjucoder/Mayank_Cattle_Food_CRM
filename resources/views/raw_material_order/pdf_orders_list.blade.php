<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Raw Material Orders</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .muted { color: #64748b; font-size: 10px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #e2e8f0; font-weight: bold; text-align: left; padding: 7px 6px; border: 1px solid #cbd5e1; font-size: 10px; }
        td { padding: 6px; border: 1px solid #e2e8f0; font-size: 10px; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Raw Material Orders</h1>
    <div class="muted">Mayank Cattle Food — Exported {{ now()->format('d M Y') }} ({{ $orders->count() }} records)</div>

    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Supplier</th>
                <th>Order Date</th>
                <th class="text-right">Total Qty (tons)</th>
                <th class="text-right">Total Price (₹)</th>
                <th class="text-right">Total Freight (₹)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orders as $order)
                <tr>
                    <td>{{ $order->order_unique_id }}</td>
                    <td>{{ $order->supplier?->name ?? '—' }}</td>
                    <td>{{ $order->order_date?->format('d M Y') ?? '—' }}</td>
                    <td class="text-right">{{ $order->total_qty }}</td>
                    <td class="text-right">{{ number_format($order->total_price, 3) }}</td>
                    <td class="text-right">{{ number_format($order->total_freight, 3) }}</td>
                    <td>{{ \App\Services\RawMaterial\RawMaterialFilterService::orderStatusLabel((int) $order->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
