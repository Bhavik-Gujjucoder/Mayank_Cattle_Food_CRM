@php
    use App\Services\DeliveryPendingPaymentsReportService;

    $items = $items ?? [];
    $modifier = $modifier ?? '';
@endphp
<span class="dpp-day-chips {{ $modifier }}">
    @foreach ($items as $item)
        @php
            $level = DeliveryPendingPaymentsReportService::dayAgingLevelFor(
                (int) $item['days'],
                (string) ($item['payment_type'] ?? 'cash')
            );
        @endphp
        <span class="dpp-day-chip dpp-day-chip--{{ $level }}">
            <span class="dpp-day-chip-num">{{ $item['days'] }}</span>
            <span class="dpp-day-chip-date">{{ $item['dispatch_date'] }}</span>
        </span>
    @endforeach
</span>
