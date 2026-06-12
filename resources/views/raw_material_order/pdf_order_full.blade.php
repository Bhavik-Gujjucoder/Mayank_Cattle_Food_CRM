<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Order — {{ $order->order_unique_id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }
        h1 { font-size: 17px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 20px 0 8px; color: #334155; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; }
        .muted { color: #64748b; font-size: 10px; }
        .header { margin-bottom: 16px; border-bottom: 2px solid #334155; padding-bottom: 10px; }
        .meta { width: 100%; margin-bottom: 8px; }
        .meta td { padding: 3px 8px 3px 0; vertical-align: top; }
        .meta .label { color: #64748b; width: 110px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.items th { background: #e2e8f0; font-weight: bold; text-align: left; padding: 6px; border: 1px solid #cbd5e1; font-size: 10px; }
        table.items td { padding: 5px 6px; border: 1px solid #e2e8f0; font-size: 10px; }
        .text-right { text-align: right; }
        .freight-sub { color: #64748b; font-size: 9px; }
        .empty { color: #94a3b8; font-style: italic; padding: 8px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Raw Material Order</h1>
        <div class="muted">Mayank Cattle Food — Full Order Export</div>
    </div>

    <h2>Section 1 — Order Details</h2>
    <table class="meta">
        <tr>
            <td class="label">Order ID</td>
            <td><strong>{{ $order->order_unique_id }}</strong></td>
            <td class="label">Order Date</td>
            <td>{{ $order->order_date?->format('d M Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Supplier</td>
            <td>{{ $order->supplier?->name ?? '—' }}</td>
            <td class="label">Supplier Order ID</td>
            <td>{{ $order->supplier_order_id ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td>{{ \App\Services\RawMaterial\RawMaterialFilterService::orderStatusLabel((int) $order->status) }}</td>
            <td class="label">Total Qty (tons)</td>
            <td>{{ $order->total_qty }}</td>
        </tr>
        <tr>
            <td class="label">Total Price (₹)</td>
            <td>{{ number_format($order->total_price, 2) }}</td>
            <td class="label">Total Freight (₹)</td>
            <td>{{ number_format($order->total_freight, 2) }}</td>
        </tr>
    </table>

    <h2>Section 2 — Order Items</h2>
    @if ($order->items->isEmpty())
        <div class="empty">No order items.</div>
    @else
        <table class="items">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Material</th>
                    <th class="text-right">Total Qty</th>
                    <th class="text-right">Pending</th>
                    <th class="text-right">Received</th>
                    <th class="text-right">Price/kg</th>
                    <th class="text-right">Avg/kg</th>
                    <th class="text-right">Total Price</th>
                    <th class="text-right">Pending Price</th>
                    <th class="text-right">Received Price</th>
                    <th class="text-right">Freight</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->rawMaterial?->name ?? '—' }}</td>
                        <td class="text-right">{{ $item->total_qty }}</td>
                        <td class="text-right">{{ $item->pending_qty }}</td>
                        <td class="text-right">{{ $item->received_qty }}</td>
                        <td class="text-right">{{ number_format($item->price, 2) }}</td>
                        <td class="text-right">{{ number_format($item->price_avg, 2) }}</td>
                        <td class="text-right">{{ number_format($item->total_price, 2) }}</td>
                        <td class="text-right">{{ number_format($item->pending_price, 2) }}</td>
                        <td class="text-right">{{ number_format($item->received_price, 2) }}</td>
                        <td class="text-right">{{ number_format($item->total_freight, 2) }}</td>
                        <td>{{ \App\Services\RawMaterial\RawMaterialFilterService::orderItemStatusLabel((int) $item->status) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Section 3 — Receive Entries</h2>
    @if ($order->receives->isEmpty())
        <div class="empty">No receive entries.</div>
    @else
        <table class="items">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Material</th>
                    <th class="text-right">Qty (tons)</th>
                    <th class="text-right">Freight</th>
                    <th>Received Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->receives as $index => $receive)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $receive->rawMaterial?->name ?? '—' }}</td>
                        <td class="text-right">{{ $receive->qty }}</td>
                        <td>{!! \App\Services\RawMaterialCacheService::receiveFreightPdfHtml($receive) !!}</td>
                        <td>{{ $receive->received_date?->format('d M Y') ?? '—' }}</td>
                        <td>{{ \App\Services\RawMaterial\RawMaterialFilterService::receiveStatusLabel((int) $receive->status) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
