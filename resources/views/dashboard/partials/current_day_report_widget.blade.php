@php
    $filters = $wr_filters ?? ['mode' => 'single', 'date' => now()->toDateString()];
    $days = $wr_days ?? [];
    $transporters = $wr_transporters ?? collect();
    $isRange = ($filters['mode'] ?? 'single') === 'range';
    $today = now()->startOfDay();
    $focusDate = $isRange ? null : \Illuminate\Support\Carbon::parse($filters['date'] ?? $today->toDateString());
    $prevDate = $focusDate?->copy()->subDay()->toDateString();
    $nextDate = $focusDate?->copy()->addDay()->toDateString();
@endphp

@once
    @include('weekly_report.partials.module-styles')
    @include('weekly_report.partials.confirmed-row-styles')
@endonce

<div class="row current-day-report-module rm-daily-summary-module weekly-report-module mb-4">
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
                                <div class="dispatch-index-title">Weekly Report</div>
                                <p class="text-muted small mb-0 mt-1">
                                    Dispatch prediction
                                    @if ($isRange)
                                        — {{ count($days) }} day(s)
                                        ({{ \Illuminate\Support\Carbon::parse($filters['date_from'])->format('d M Y') }}
                                        – {{ \Illuminate\Support\Carbon::parse($filters['date_to'])->format('d M Y') }})
                                    @else
                                        — {{ $focusDate->format('d M Y') }}
                                        ({{ strtoupper($focusDate->format('l')) }})
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-auto">
                        <div class="wr-header-toolbar">
                            @if (! $isRange && $prevDate && $nextDate)
                                <div class="wr-date-nav">
                                    <a href="{{ route('dashboard', ['wr_date' => $prevDate]) }}"
                                        class="btn btn-light btn-sm" title="Previous day">
                                        <i class="ti ti-chevron-left"></i>
                                    </a>
                                    <input type="text" class="form-control form-control-sm flatpickr wr-dash-single-date-picker"
                                        id="wrDashSingleDate" value="{{ $filters['date'] ?? $today->toDateString() }}"
                                        placeholder="DD-MM-YYYY" autocomplete="off">
                                    <a href="{{ route('dashboard', ['wr_date' => $nextDate]) }}"
                                        class="btn btn-light btn-sm" title="Next day">
                                        <i class="ti ti-chevron-right"></i>
                                    </a>
                                </div>
                            @endif
                            <div class="comm-header-right-btn">
                                <a href="{{ route('weekly-report.index', array_filter([
                                    'date' => $isRange ? null : ($filters['date'] ?? $today->toDateString()),
                                    'date_from' => $filters['date_from'] ?? null,
                                    'date_to' => $filters['date_to'] ?? null,
                                ])) }}" class="btn btn-light btn-md">
                                    <i class="ti ti-maximize me-1"></i>Open full page
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="get" action="{{ route('dashboard') }}" id="wrDashboardFilterForm" class="mt-3 pt-3 border-top">
                    <div class="cls-cardhed-part">
                        <div class="cls-form-left wr-filter-bar">
                            <div class="common-hed-form cls-form-select-input">
                                <label class="col-form-label" for="wrDashDateFrom">From Date</label>
                                <div class="icon-form">
                                    <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                                    <input type="text" name="wr_date_from" id="wrDashDateFrom"
                                        value="{{ $filters['date_from'] ?? '' }}"
                                        class="form-control flatpickr wr-dash-flatpickr" placeholder="DD-MM-YYYY" autocomplete="off">
                                </div>
                            </div>
                            <div class="common-hed-form cls-form-select-input">
                                <label class="col-form-label" for="wrDashDateTo">To Date</label>
                                <div class="icon-form">
                                    <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                                    <input type="text" name="wr_date_to" id="wrDashDateTo"
                                        value="{{ $filters['date_to'] ?? '' }}"
                                        class="form-control flatpickr wr-dash-flatpickr" placeholder="DD-MM-YYYY" autocomplete="off">
                                </div>
                            </div>
                            <div class="wr-filter-actions">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="ti ti-filter me-1"></i>Apply
                                </button>
                                <a href="{{ route('dashboard') }}" class="btn btn-danger btn-sm">
                                    <i class="ti ti-refresh me-1"></i>Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body pt-3">
                @if (! empty($wr_filter_error))
                    <div class="alert alert-warning py-2 mb-3">{{ $wr_filter_error }}</div>
                @endif

                @forelse ($days as $day)
                    @include('weekly_report.partials.day-block', [
                        'day' => $day,
                        'transporters' => $transporters,
                    ])
                @empty
                    <div class="wr-empty-state">
                        <i class="ti ti-calendar-off"></i>
                        No report data for the selected date(s).
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@if (auth()->user()->can('edit-weekly-report') && auth()->user()->can('add-dispatch'))
<div class="modal fade" id="confirmItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-check me-2"></i>Confirm &amp; Create Dispatch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="confirmItemForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small">
                        This will create a dispatch entry for the report date. The row will become read-only after confirmation.
                    </p>
                    <div class="row">
                        @include('dispatch_management.partials.status-field', [
                            'idPrefix' => 'wrConfirmDash',
                            'value' => '0',
                        ])
                    </div>
                    <div class="text-danger small" id="confirmFormError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="confirmSubmitBtn">
                        <i class="ti ti-check me-1"></i>Confirm dispatch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@include('weekly_report.partials.quick-add-modals')
