@php $salesStartNewPage = $salesStartNewPage ?? false; @endphp
@foreach (['brand', 'broker', 'product'] as $key)
    @php $section = $payload[$key]; @endphp
    <div class="section {{ $loop->first && ! $salesStartNewPage ? 'section-first' : '' }}">
        <div class="header sales-header">
            <h1>Daily Sales Sheets — {{ $section['heading'] }}</h1>
            <div class="muted">As of {{ $payload['as_of']->format('d.m.Y') }}</div>
        </div>

        @if (empty($section['groups']))
            <div class="empty">No pending orders.</div>
        @else
            @foreach ($section['groups'] as $group)
                <div class="group">
                    <h3>{{ $group['name'] }}</h3>
                    <table class="sales-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Party Name</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Rate</th>
                                <th class="text-right">Dispatch</th>
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
                                    <td class="text-right">{{ number_format((int) ($row['dispatch'] ?? 0)) }}</td>
                                    <td class="text-right">{{ number_format((int) $row['pending']) }}</td>
                                    <td>{{ $row['last_loading'] }}</td>
                                </tr>
                            @endforeach
                            <tr class="total-row">
                                <td></td>
                                <td>Total</td>
                                <td class="text-right">{{ number_format((int) $group['totals']['total']) }}</td>
                                <td></td>
                                <td class="text-right">{{ number_format((int) ($group['totals']['dispatch'] ?? 0)) }}</td>
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
