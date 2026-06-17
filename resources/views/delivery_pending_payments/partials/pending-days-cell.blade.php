@php
    $items = $row['pending_days_items'] ?? [];
@endphp
<span class="dpp-days-value fw-medium">
    {{-- Screen: day counts with hover tooltip, color per aging band --}}
    <span class="dpp-days-screen">
        @foreach ($items as $index => $item)
            @php
                $level = \App\Services\DeliveryPendingPaymentsReportService::dayAgingLevelFor((int) $item['days']);
            @endphp
            @if ($index > 0)<span class="dpp-days-sep"> - </span>@endif
            <span class="dpp-day-pill dpp-day-pill--{{ $level }}"
                @if (!empty($canUpdateDispatchPayment)) data-dispatch-id="{{ $item['dispatch_id'] ?? '' }}" @endif
                data-bs-toggle="tooltip"
                data-bs-placement="top"
                data-bs-title="Dispatch date: {{ $item['dispatch_date'] }}"
                tabindex="0">{{ $item['days'] }}</span>
        @endforeach
    </span>
    {{-- Print / PDF: chip badges (same design as mobile, print-only styles) --}}
    <span class="dpp-days-print">
        @include('delivery_pending_payments.partials.pending-days-chips', [
            'items' => $items,
            'modifier' => 'dpp-day-chips--print',
        ])
    </span>
</span>
