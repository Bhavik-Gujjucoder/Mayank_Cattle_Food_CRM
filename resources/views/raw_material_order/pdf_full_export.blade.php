<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Raw Material Full Export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        h2 { font-size: 12px; margin: 18px 0 8px; color: #334155; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; page-break-after: avoid; }
        .muted { color: #64748b; font-size: 10px; margin-bottom: 12px; }
        .header { margin-bottom: 14px; border-bottom: 2px solid #334155; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th { background: #e2e8f0; font-weight: bold; text-align: left; padding: 5px 4px; border: 1px solid #cbd5e1; font-size: 9px; }
        td { padding: 4px; border: 1px solid #e2e8f0; font-size: 9px; vertical-align: top; }
        .text-right { text-align: right; }
        .freight-sub { color: #64748b; font-size: 9px; }
        .empty { color: #94a3b8; font-style: italic; padding: 6px 0; }
        .section { page-break-inside: avoid; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Raw Material — Full Export</h1>
        <div class="muted">Mayank Cattle Food — Exported {{ now()->format('d M Y') }}</div>
    </div>

    <div class="section">
        <h2>Section 1 — All Orders ({{ $orders->count() }})</h2>
        @if ($orders->isEmpty())
            <div class="empty">No orders.</div>
        @else
            @include('raw_material_order.partials.orders-list-table', ['orders' => $orders])
        @endif
    </div>

    <div class="section">
        <h2>Section 2 — All Order Items ({{ $items->count() }})</h2>
        @if ($items->isEmpty())
            <div class="empty">No order items.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
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
                    @foreach ($items as $item)
                        <tr>
                            <td>{{ $item->order?->order_unique_id ?? '—' }}</td>
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
    </div>

    <div class="section">
        <h2>Section 3 — All Receives ({{ $receives->count() }})</h2>
        @if ($receives->isEmpty())
            <div class="empty">No receive entries.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Supplier Order ID</th>
                        <th>Material</th>
                        <th class="text-right">Qty (tons)</th>
                        <th class="text-right">Freight</th>
                        <th>Received Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($receives as $receive)
                        <tr>
                            <td>{{ $receive->order?->order_unique_id ?? '—' }}</td>
                            <td>{{ $receive->order?->supplier_order_id ?: '—' }}</td>
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
    </div>
</body>
</html>
