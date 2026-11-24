<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 16px 0 6px; }
        .muted { color: #64748b; font-size: 10px; margin-bottom: 10px; }
        .section-label { font-weight: bold; margin: 8px 0 4px; color: #3e6eaf; }
        .order-block { border: 1px solid #3e6eaf; margin: 0 0 12px; page-break-inside: avoid; }
        .order-meta { width: 100%; border-collapse: collapse; background: #3e6eaf; color: #fff; }
        .order-meta td { padding: 4px 6px; font-size: 8px; border: none; }
        .order-meta .label { color: #d6e4f5; }
        .product-block { margin-top: 8px; border: 1px solid #d6e4f5; }
        table.data { width: 100%; border-collapse: collapse; margin: 0; }
        table.data th { background: #d6e4f5; color: #014e9c; font-weight: bold; text-align: left; padding: 4px; border: 1px solid #b9cfe6; font-size: 8px; }
        table.data td { padding: 4px; border: 1px solid #e2e8f0; font-size: 8px; }
        table.dispatch th { background: #eef3f9; color: #3e6eaf; }
        table.cards { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.cards th { background: #3e6eaf; color: #fff; font-weight: bold; text-align: left; padding: 5px 4px; border: 1px solid #3e6eaf; font-size: 8px; }
        table.cards td { padding: 4px; border: 1px solid #d6e4f5; font-size: 8px; }
        .pad { padding: 6px 8px 8px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="muted">Mayank Cattle Food — Exported {{ now()->format('d M Y') }} ({{ count($groups) }} orders)</div>

    <h2>Cards</h2>
    <table class="cards">
        <thead>
            <tr>
                @foreach ($cardHeadings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($cards as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($cardHeadings) }}">No records</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Orders</h2>
    @forelse ($groups as $group)
        <div class="order-block">
            <table class="order-meta">
                <tr>
                    @foreach ($group['meta'] as $field)
                        <td>
                            <div class="label">{{ $field['label'] }}</div>
                            <div>{{ $field['value'] }}</div>
                        </td>
                    @endforeach
                </tr>
            </table>
            <div class="pad">
                @forelse ($group['products'] as $product)
                    <div class="product-block">
                        <table class="data">
                            <thead>
                                <tr>
                                    @foreach ($lineHeadings as $heading)
                                        <th>{{ $heading }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    @foreach ($product['line'] as $cell)
                                        <td>{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                        <table class="data dispatch">
                            <thead>
                                <tr>
                                    @foreach ($dispatchHeadings as $heading)
                                        <th>{{ $heading }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($product['dispatches'] as $dispatch)
                                    <tr>
                                        @foreach ($dispatch as $cell)
                                            <td>{{ $cell }}</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ count($dispatchHeadings) }}">No dispatch</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @empty
                    <p>No products</p>
                @endforelse
            </div>
        </div>
    @empty
        <p>No orders</p>
    @endforelse
</body>
</html>
