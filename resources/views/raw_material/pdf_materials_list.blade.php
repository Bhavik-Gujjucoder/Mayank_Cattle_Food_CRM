<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Raw Materials</title>
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
    <h1>Raw Materials</h1>
    <div class="muted">Mayank Cattle Food — Exported {{ now()->format('d M Y') }} ({{ $materials->count() }} records)</div>

    <table>
        <thead>
            <tr>
                <th>Material ID</th>
                <th>Category</th>
                <th>Name</th>
                <th>Unit</th>
                <th class="text-right">Total Stock</th>
                <th class="text-right">Available Stock</th>
                <th class="text-right">Last Price/kg</th>
                <th class="text-right">Avg Price/kg</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($materials as $material)
                <tr>
                    <td>{{ $material->raw_material_unique_id }}</td>
                    <td>{{ $material->category?->name ?? '—' }}</td>
                    <td>{{ $material->name }}</td>
                    <td>{{ $material->unit }}</td>
                    <td class="text-right">{{ number_format((float) $material->total_stock, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $material->available_stock, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $material->last_purchase_price, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $material->average_price, 2) }}</td>
                    <td>{{ \App\Services\RawMaterial\RawMaterialFilterService::materialStatusLabel((int) $material->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
