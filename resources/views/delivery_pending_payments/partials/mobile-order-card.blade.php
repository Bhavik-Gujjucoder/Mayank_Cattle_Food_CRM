@php
    use App\Services\DeliveryPendingPaymentsReportService;
    use App\Services\PaymentReceivableService;

    $items = $row['pending_days_items'] ?? [];
    $maxDays = (int) ($row['max_pending_days'] ?? 0);
    $emphasisClass = $row['days_emphasis_class'] ?? 'text-success';
@endphp
<article class="dpp-mobile-order">
    <div class="dpp-mobile-order-head">
        <div class="dpp-mobile-order-main">
            @if ($canLinkOrder)
                <a href="{{ route('dispatch.orderHistory', $row['order_id']) }}" class="dpp-mobile-order-id">
                    {{ $row['order_label'] }}
                </a>
            @else
                <span class="dpp-mobile-order-id">{{ $row['order_label'] }}</span>
            @endif
            <div class="dpp-mobile-order-meta">
                <span class="dpp-mobile-meta-item">
                    <i class="ti ti-map-pin"></i>{{ $row['city_name'] }}
                </span>
                <span class="dpp-mobile-meta-sep" aria-hidden="true">·</span>
                <span class="dpp-mobile-meta-item text-truncate">{{ $row['dealer_name'] }}</span>
            </div>
        </div>
        @if ($maxDays > 0)
            <span class="dpp-mobile-max-badge {{ $emphasisClass }}">{{ $maxDays }}d</span>
        @endif
    </div>

    @if (count($items) > 0)
        <div class="dpp-mobile-order-amounts small text-muted mb-2">
            <span>Late fee: <strong class="text-dark">{{ PaymentReceivableService::formatMoney((float) ($row['total_late_fee'] ?? 0)) }}</strong></span>
            <span class="mx-1" aria-hidden="true">·</span>
            <span>Balance: <strong class="text-dark">{{ PaymentReceivableService::formatMoney((float) ($row['total_balance_due'] ?? 0)) }}</strong></span>
        </div>
        <div class="dpp-mobile-order-days">
            <div class="dpp-mobile-days-title">Pending payment days</div>
            <div class="dpp-day-chips dpp-day-chips--mobile" role="list">
                @foreach ($items as $item)
                    @php
                        $level = DeliveryPendingPaymentsReportService::dayAgingLevelFor((int) $item['days']);
                    @endphp
                    <span class="dpp-day-chip dpp-day-chip--{{ $level }}" role="listitem"
                        @if (!empty($canUpdateDispatchPayment)) data-dispatch-id="{{ $item['dispatch_id'] ?? '' }}" @endif
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        data-bs-title="Dispatch date: {{ $item['dispatch_date'] }}"
                        tabindex="0">
                        <span class="dpp-day-chip-num">{{ $item['days'] }}</span>
                        <span class="dpp-day-chip-date">{{ $item['dispatch_date'] }}</span>
                    </span>
                @endforeach
            </div>
        </div>
    @endif
</article>
