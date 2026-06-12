<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Raw Material Orders</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .muted { color: #64748b; font-size: 10px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #e2e8f0; font-weight: bold; text-align: left; padding: 6px 4px; border: 1px solid #cbd5e1; font-size: 9px; }
        td { padding: 5px 4px; border: 1px solid #e2e8f0; font-size: 9px; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Raw Material Orders</h1>
    <div class="muted">Mayank Cattle Food — Exported {{ now()->format('d M Y') }} ({{ $orders->count() }} records)</div>

    @include('raw_material_order.partials.orders-list-table', ['orders' => $orders])
</body>
</html>
