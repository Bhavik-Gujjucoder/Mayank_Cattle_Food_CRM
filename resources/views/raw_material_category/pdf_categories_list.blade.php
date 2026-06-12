<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Raw Material Categories</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .muted { color: #64748b; font-size: 10px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #e2e8f0; font-weight: bold; text-align: left; padding: 7px 6px; border: 1px solid #cbd5e1; font-size: 10px; }
        td { padding: 6px; border: 1px solid #e2e8f0; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Raw Material Categories</h1>
    <div class="muted">Mayank Cattle Food — Exported {{ now()->format('d M Y') }} ({{ $categories->count() }} records)</div>

    <table>
        <thead>
            <tr>
                <th>Category ID</th>
                <th>Name</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($categories as $category)
                <tr>
                    <td>{{ $category->category_unique_id }}</td>
                    <td>{{ $category->name }}</td>
                    <td>{{ \App\Services\RawMaterial\RawMaterialFilterService::materialStatusLabel((int) $category->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
