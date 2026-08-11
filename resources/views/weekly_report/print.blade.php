@php
    use App\Models\WeeklyReportItem;
    use App\Support\ProductUnit;

    $isRange = ($filters['mode'] ?? 'single') === 'range';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page_title ?? 'Daily Dispatch' }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            color: #262A2A;
            background: #fff;
            padding: 1.25rem;
        }

        .wr-print-header {
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #3e6eaf;
        }

        .wr-print-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: #262A2A;
            margin: 0 0 0.25rem;
        }

        .wr-print-meta {
            font-size: 0.85rem;
            color: #64748b;
            margin: 0;
        }

        .wr-print-toolbar {
            margin-bottom: 1rem;
        }

        .wr-print-day {
            margin-bottom: 1.5rem;
            page-break-inside: avoid;
        }

        .wr-print-day-title {
            font-size: 1rem;
            font-weight: 700;
            color: #3e6eaf;
            background: linear-gradient(90deg, rgba(62, 110, 175, 0.08) 0%, #fff 60%);
            border: 1px solid #E8E8E8;
            border-bottom: none;
            padding: 0.55rem 0.85rem;
            margin: 0;
        }

        .wr-print-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.75rem;
        }

        .wr-print-table thead th {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #3e6eaf;
            background: linear-gradient(180deg, #f0f4fa 0%, #f8f9fa 100%);
            border: 1px solid #E8E8E8;
            padding: 6px 8px;
            white-space: nowrap;
        }

        .wr-print-table tbody td {
            border: 1px solid #E8E8E8;
            padding: 6px 8px;
            vertical-align: top;
            word-break: break-word;
        }

        .wr-print-table tbody tr.wr-print-row-pending:nth-child(odd) td {
            background: #fff;
            color: #475569;
        }

        .wr-print-table tbody tr.wr-print-row-pending:nth-child(even) td {
            background: #f8fafc;
            color: #475569;
        }

        .wr-print-table tbody tr.wr-print-row-pending td:first-child {
            box-shadow: inset 3px 0 0 #3e6eaf;
        }

        .wr-print-table tbody tr.wr-print-row-confirmed td {
            background: #ecfdf5 !important;
            border-color: #bbf7d0 !important;
            color: #166534;
        }

        .wr-print-table tbody tr.wr-print-row-confirmed td:first-child {
            box-shadow: inset 4px 0 0 #10b981;
        }

        .wr-print-order-id {
            color: #3e6eaf;
            font-weight: 600;
        }

        .wr-print-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 600;
            border-radius: 999px;
            padding: 0.15rem 0.5rem;
        }

        .wr-print-badge--pending {
            background: #FFEECD;
            color: #B45309;
        }

        .wr-print-badge--confirmed {
            background: #D1FAE5;
            color: #047857;
            border: 1px solid #6ee7b7;
        }

        .wr-print-summary {
            border: 1px solid #E8E8E8;
            border-radius: 6px;
            padding: 0.75rem;
            background: #f8fafc;
        }

        .wr-print-summary-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #3e6eaf;
            margin-bottom: 0.5rem;
        }

        .wr-print-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 0.5rem;
        }

        .wr-print-summary-item label {
            display: block;
            font-size: 10px;
            color: #64748b;
            margin-bottom: 0.15rem;
        }

        .wr-print-summary-item span {
            font-weight: 600;
            color: #262A2A;
        }

        .wr-print-empty {
            padding: 0.75rem;
            border: 1px dashed #cbd5e1;
            color: #64748b;
            font-style: italic;
            background: #f8fafc;
        }

        .wr-print-footnote {
            margin-top: 1.5rem;
            padding-top: 0.75rem;
            border-top: 1px solid #E8E8E8;
            font-size: 10px;
            color: #64748b;
        }

        @media print {
            body {
                padding: 0;
            }

            .wr-print-toolbar,
            .no-print {
                display: none !important;
            }

            .wr-print-day {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="wr-print-toolbar no-print d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print();">
            <i class="ti ti-printer"></i> Print
        </button>
        <button type="button" class="btn btn-light btn-sm" onclick="window.close();">Close</button>
    </div>

    <div class="wr-print-header">
        <h1 class="wr-print-title">Daily Dispatch — Dispatch Prediction</h1>
        <p class="wr-print-meta">
            @if ($isRange)
                {{ count($days) }} day(s):
                {{ \Illuminate\Support\Carbon::parse($filters['date_from'])->format('d M Y') }}
                – {{ \Illuminate\Support\Carbon::parse($filters['date_to'])->format('d M Y') }}
            @else
                {{ \Illuminate\Support\Carbon::parse($filters['date'] ?? now())->format('d M Y') }}
                ({{ strtoupper(\Illuminate\Support\Carbon::parse($filters['date'] ?? now())->format('l')) }})
            @endif
            · Printed {{ now()->format('d M Y, h:i A') }}
        </p>
    </div>

    @forelse ($days as $day)
        @php
            $report = $day['report'] ?? null;
            $dayDate = $day['date'];
        @endphp
        <div class="wr-print-day">
            <h2 class="wr-print-day-title">
                {{ $dayDate->format('d M Y') }} — {{ strtoupper($dayDate->format('l')) }}
            </h2>

            @if (! $report)
                <div class="wr-print-empty">No report exists for this date.</div>
            @else
                @if ($report->items->isEmpty())
                    <div class="wr-print-empty">No planned orders for this date.</div>
                @else
                    <table class="wr-print-table">
                        <thead>
                            <tr>
                                <th>Order No</th>
                                <th>Order ID</th>
                                <th>Product</th>
                                <th>Dealer</th>
                                <th>City</th>
                                <th>Qty</th>
                                <th>Transport</th>
                                <th>Truck No</th>
                                <th>Contact</th>
                                <th>Note</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report->items as $item)
                                @php
                                    $dealer = $item->order?->dealer;
                                    $confirmed = $item->isConfirmed();
                                @endphp
                                <tr class="{{ $confirmed ? 'wr-print-row-confirmed' : 'wr-print-row-pending' }}">
                                    <td>{{ $item->sort_order }}</td>
                                    <td class="wr-print-order-id">{{ $item->order?->unique_order_id ?? '—' }}</td>
                                    <td>{{ $item->product?->name ?? '—' }}</td>
                                    <td>{{ $dealer?->user?->name ?? $dealer?->firm_shop_name ?? '—' }}</td>
                                    <td>{{ $dealer?->city?->city_name ?? '—' }}</td>
                                    <td>{{ ProductUnit::formatWithUnit($item->quantity, $item->product?->unit) }}</td>
                                    <td>{{ $item->transporter?->name ?? '—' }}</td>
                                    <td>{{ $item->truck_number ?? '—' }}</td>
                                    <td>{{ $item->driver_contact ?? '—' }}</td>
                                    <td>{{ $item->note ?: '—' }}</td>
                                    <td>
                                        <span class="wr-print-badge {{ $confirmed ? 'wr-print-badge--confirmed' : 'wr-print-badge--pending' }}">
                                            {{ $confirmed ? 'Confirmed' : 'Pending' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <div class="wr-print-summary">
                    <div class="wr-print-summary-title">Production summary</div>
                    <div class="wr-print-summary-grid">
                        <div class="wr-print-summary-item">
                            <label>Total quantity (bags)</label>
                            <span>{{ number_format($report->totalQuantityInBags(), 2) }}</span>
                        </div>
                        <div class="wr-print-summary-item">
                            <label>Already produced / ready stock</label>
                            <span>{{ number_format((float) $report->already_produced, 2) }}</span>
                        </div>
                        <div class="wr-print-summary-item">
                            <label>Difference</label>
                            <span>{{ number_format($report->differenceInBags(), 2) }}</span>
                        </div>
                        <div class="wr-print-summary-item">
                            <label>Bags per hour (divisor)</label>
                            <span>{{ number_format($report->bagsPerHour(), 2) }}</span>
                        </div>
                        <div class="wr-print-summary-item">
                            <label>Production hours (÷ {{ number_format($report->bagsPerHour(), 0) }})</label>
                            <span>{{ number_format($report->productionHours(), 2) }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @empty
        <div class="wr-print-empty">No dates to display for the selected filter.</div>
    @endforelse

    <div class="wr-print-footnote">
        Pending rows: white/light grey with blue accent. Confirmed rows: green background.
        Production summary: difference = total quantity minus ready stock.
    </div>

    @if ($autoPrint ?? false)
        <script>window.onload = function () { window.print(); };</script>
    @endif
</body>
</html>
