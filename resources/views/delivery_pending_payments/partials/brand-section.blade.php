@php
    use App\Services\DeliveryPendingPaymentsReportService;

    $rows = $section['rows'] ?? [];
    $brandTitle = DeliveryPendingPaymentsReportService::formatBrandSectionTitle($section['brand_name'] ?? '');
    $orderCount = count($rows);
@endphp

<section class="dpp-brand-section">
    <div class="dpp-brand-section-head">
        <h6 class="dpp-brand-section-title mb-0">{{ $brandTitle }}</h6>
        <span class="badge bg-light text-dark">{{ $orderCount }} {{ $orderCount === 1 ? 'order' : 'orders' }}</span>
    </div>

    <div class="table-responsive custom-table dpp-table-wrap d-none d-md-block">
        <table class="table mb-0 dpp-report-table">
            <thead class="thead-light">
                <tr>
                    <th class="dpp-col-city">City</th>
                    <th class="dpp-col-dealer">Dealer</th>
                    <th class="dpp-col-order">Order</th>
                    <th class="dpp-col-days">Pending Payment Days</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['city_name'] }}</td>
                        <td>{{ $row['dealer_name'] }}</td>
                        <td>
                            @if ($canLinkOrder)
                                <a href="{{ route('dispatch.orderHistory', $row['order_id']) }}"
                                    class="fw-medium">{{ $row['order_label'] }}</a>
                            @else
                                {{ $row['order_label'] }}
                            @endif
                        </td>
                        <td class="dpp-days-col">
                            @include('delivery_pending_payments.partials.pending-days-cell', ['row' => $row])
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="d-md-none dpp-mobile-list">
        @foreach ($rows as $row)
            @include('delivery_pending_payments.partials.mobile-order-card', [
                'row' => $row,
                'canLinkOrder' => $canLinkOrder,
            ])
        @endforeach
    </div>
</section>
