@php
    $summary = $dpp_dashboard_summary ?? ['order_count' => 0, 'dispatch_count' => 0, 'brand_count' => 0];
    $minDays = $dpp_dashboard_min_days ?? 10;
@endphp

<div class="col-12 d-flex">
    <div class="card flex-fill recent-cards delivery-pending-payments-module dashboard-dpp-widget w-100">
        <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="mb-1">Dispatch Pending Payments ({{ $minDays }}+ Days)</h5>
                <p class="fs-13 text-muted mb-0">
                    Unpaid dispatches with {{ $minDays }} or more pending payment days
                </p>
            </div>
            <a href="{{ route('delivery-pending-payments.index') }}" class="btn btn-light btn-md mb-0">
                View Full Report
            </a>
        </div>
        <div class="card-body pb-2">
            <div class="row g-2 mb-3">
                <div class="col-sm-4">
                    <div class="dashboard-dpp-stat-pill">
                        <span class="dashboard-dpp-stat-num">{{ $summary['order_count'] }}</span>
                        <span class="dashboard-dpp-stat-label">Orders</span>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="dashboard-dpp-stat-pill">
                        <span class="dashboard-dpp-stat-num">{{ $summary['dispatch_count'] }}</span>
                        <span class="dashboard-dpp-stat-label">Unpaid Dispatches</span>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="dashboard-dpp-stat-pill">
                        <span class="dashboard-dpp-stat-num">{{ $summary['brand_count'] }}</span>
                        <span class="dashboard-dpp-stat-label">Brands</span>
                    </div>
                </div>
            </div>

            @if ($dpp_dashboard_sections->isEmpty())
                <div class="text-center py-4">
                    <i class="ti ti-circle-check text-success fs-1 mb-2 d-block"></i>
                    <p class="text-muted mb-0">No unpaid dispatch payments at {{ $minDays }}+ days.</p>
                </div>
            @else
                <div class="dpp-brands-stack dashboard-dpp-brands-stack">
                    @foreach ($dpp_dashboard_sections as $section)
                        @include('delivery_pending_payments.partials.brand-section', [
                            'section' => $section,
                            'canLinkOrder' => $dpp_dashboard_can_link_order,
                        ])
                    @endforeach
                </div>
            @endif

            <p class="fs-12 text-muted mb-0 mt-3 pt-2 border-top">
                Days count from dispatch date to today (unpaid dispatches only). Shown: {{ $minDays }}+ days per dispatch.
            </p>
        </div>
    </div>
</div>
