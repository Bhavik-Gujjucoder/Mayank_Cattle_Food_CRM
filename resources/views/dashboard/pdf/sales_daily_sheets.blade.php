<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Daily Sales Sheets — {{ $payload['as_of']->format('d.m.Y') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        h2 { font-size: 13px; margin: 0 0 8px; color: #1e3a8a; }
        h3 { font-size: 11px; margin: 0 0 6px; color: #1e3a8a; background: #dbeafe; padding: 5px 8px; }
        .muted { color: #64748b; font-size: 10px; margin-bottom: 10px; }
        .section { page-break-before: always; }
        .section-first { page-break-before: auto; }
        .group { margin-bottom: 14px; page-break-inside: avoid; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        th { background: #f0f4fa; font-weight: bold; text-align: left; padding: 5px 6px; border: 1px solid #cbd5e1; font-size: 9px; color: #3e6eaf; }
        td { padding: 4px 6px; border: 1px solid #e2e8f0; font-size: 9px; }
        .text-right { text-align: right; }
        .total-row td { background: #fef3c7; font-weight: bold; }
        .empty { color: #94a3b8; font-style: italic; padding: 6px 0; }
        .header { margin-bottom: 12px; border-bottom: 2px solid #1e3a8a; padding-bottom: 8px; }
    </style>
</head>
<body>
    @foreach (['brand', 'broker', 'product'] as $key)
        @php $section = $payload[$key]; @endphp
        <div class="section {{ $loop->first ? 'section-first' : '' }}">
            <div class="header">
                <h1>Daily Sales Sheets — {{ $section['heading'] }}</h1>
                <div class="muted">As of {{ $payload['as_of']->format('d.m.Y') }}</div>
            </div>

            @if (empty($section['groups']))
                <div class="empty">No pending orders.</div>
            @else
                @foreach ($section['groups'] as $group)
                    <div class="group">
                        <h3>{{ $group['name'] }}</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Party Name</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-right">Rate</th>
                                    <th class="text-right">Pending</th>
                                    <th>Last Loading</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($group['rows'] as $row)
                                    <tr>
                                        <td>{{ $row['order_date'] }}</td>
                                        <td>{{ $row['party_name'] }}</td>
                                        <td class="text-right">{{ number_format((int) $row['total']) }}</td>
                                        <td class="text-right">{{ number_format((float) $row['rate'], 2) }}</td>
                                        <td class="text-right">{{ number_format((int) $row['pending']) }}</td>
                                        <td>{{ $row['last_loading'] }}</td>
                                    </tr>
                                @endforeach
                                <tr class="total-row">
                                    <td></td>
                                    <td>Total</td>
                                    <td class="text-right">{{ number_format((int) $group['totals']['total']) }}</td>
                                    <td></td>
                                    <td class="text-right">{{ number_format((int) $group['totals']['pending']) }}</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endforeach
            @endif
        </div>
    @endforeach
</body>
</html>
