@php
    use App\Support\ProductUnit;

    $report = $today_weekly_report ?? null;
    $today = now()->startOfDay();
@endphp

<div class="row current-day-report-module rm-daily-summary-module mb-4">
    <div class="col-12 d-flex">
        <div class="card flex-fill w-100 recent-cards">
            <div class="card-header pb-2">
                <div class="row align-items-center g-3 mb-0">
                    <div class="col-12 col-sm-auto me-auto">
                        <div class="d-flex align-items-center gap-2">
                            <div class="dispatch-index-icon">
                                <i class="ti ti-calendar-event"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="dispatch-index-eyebrow">Sales</div>
                                <div class="dispatch-index-title">Current Day Report</div>
                                <p class="text-muted small mb-0 mt-1">
                                    Dispatch prediction — {{ $today->format('d M Y') }}
                                    ({{ strtoupper($today->format('l')) }})
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-auto">
                        <div class="comm-header-right-btn">
                            @if ($report)
                                <a href="{{ route('weekly-report.show', $report->id) }}" class="btn btn-primary btn-md">
                                    <i class="ti ti-eye me-1"></i>Open Report
                                </a>
                            @elseif (auth()->user()->can('add-weekly-report'))
                                <a href="{{ route('weekly-report.create') }}" class="btn btn-primary btn-md">
                                    <i class="ti ti-square-rounded-plus me-1"></i>Create Report
                                </a>
                            @endif
                            <a href="{{ route('weekly-report.index') }}" class="btn btn-light btn-md">
                                <i class="ti ti-list me-1"></i>View All
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @if (! $report)
                    <p class="text-muted mb-0">No report has been created for today yet.</p>
                @else
                    @php
                        $confirmedCount = $report->items->filter(fn ($item) => $item->isConfirmed())->count();
                    @endphp

                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-4 col-xl">
                            <div class="rm-kpi-pill">
                                <span class="rm-kpi-label">Rows</span>
                                <strong>{{ $report->items->count() }}</strong>
                                <span class="text-muted small">{{ $confirmedCount }} confirmed</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-xl">
                            <div class="rm-kpi-pill">
                                <span class="rm-kpi-label">Total Qty (bags)</span>
                                <strong>{{ number_format($report->totalQuantityInBags(), 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-xl">
                            <div class="rm-kpi-pill">
                                <span class="rm-kpi-label">Already Produced</span>
                                <strong>{{ number_format((float) $report->already_produced, 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-xl">
                            <div class="rm-kpi-pill">
                                <span class="rm-kpi-label">Difference</span>
                                <strong>{{ number_format($report->differenceInBags(), 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-xl">
                            <div class="rm-kpi-pill">
                                <span class="rm-kpi-label">Production Hours</span>
                                <strong>{{ number_format($report->productionHours(), 2) }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive custom-table">
                        <table class="table table-hover table-nowrap mb-0 dashboard-recent-table w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>Sr</th>
                                    <th>Order ID</th>
                                    <th>Product</th>
                                    <th>Dealer</th>
                                    <th>City</th>
                                    <th>Qty</th>
                                    <th>Transport</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($report->items as $index => $item)
                                    @php
                                        $dealer = $item->order?->dealer;
                                        $confirmed = $item->isConfirmed();
                                    @endphp
                                    <tr class="{{ $confirmed ? 'wr-row-confirmed' : '' }}">
                                        <td><div class="wr-readonly-value">{{ $index + 1 }}</div></td>
                                        <td><div class="wr-readonly-value">{{ $item->order?->unique_order_id ?? '—' }}</div></td>
                                        <td><div class="wr-readonly-value">{{ $item->product?->name ?? '—' }}</div></td>
                                        <td><div class="wr-readonly-value">{{ $dealer?->user?->name ?? $dealer?->firm_shop_name ?? '—' }}</div></td>
                                        <td><div class="wr-readonly-value">{{ $dealer?->city?->city_name ?? '—' }}</div></td>
                                        <td><div class="wr-readonly-value">{{ ProductUnit::formatWithUnit($item->quantity, $item->product?->unit) }}</div></td>
                                        <td><div class="wr-readonly-value">{{ $item->transporter?->name ?? '—' }}</div></td>
                                        <td><div class="wr-readonly-value">{!! $item->statusBadge() !!}</div></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            No rows planned for today yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
