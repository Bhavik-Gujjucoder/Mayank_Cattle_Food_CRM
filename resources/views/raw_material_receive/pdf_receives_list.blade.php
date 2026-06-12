<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Raw Material Received</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .muted { color: #64748b; font-size: 10px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #e2e8f0; font-weight: bold; text-align: left; padding: 7px 6px; border: 1px solid #cbd5e1; font-size: 10px; }
        td { padding: 6px; border: 1px solid #e2e8f0; font-size: 10px; }
        .text-right { text-align: right; }
        .freight-sub { color: #64748b; font-size: 9px; }
    </style>
</head>
<body>
    <h1>Raw Material Received</h1>
    <div class="muted">Mayank Cattle Food — Exported {{ now()->format('d M Y') }} ({{ $receives->count() }} records)</div>

    <table>
        <thead>
            <tr>
                <th>Sr No</th>
                <th>Order ID</th>
                <th>Supplier Order ID</th>
                <th>Category</th>
                <th>Material</th>
                <th class="text-right">Qty (tons)</th>
                <th>Freight</th>
                <th>Received Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($receives as $index => $receive)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $receive->order?->order_unique_id ?? '—' }}</td>
                    <td>{{ $receive->order?->supplier_order_id ?: '—' }}</td>
                    <td>{{ $receive->rawMaterial?->category?->name ?? '—' }}</td>
                    <td>{{ $receive->rawMaterial?->name ?? '—' }}</td>
                    <td class="text-right">{{ $receive->qty }}</td>
                    <td>{!! \App\Services\RawMaterialCacheService::receiveFreightPdfHtml($receive) !!}</td>
                    <td>{{ $receive->received_date?->format('d M Y') ?? '—' }}</td>
                    <td>{{ \App\Services\RawMaterial\RawMaterialFilterService::receiveStatusLabel((int) $receive->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
