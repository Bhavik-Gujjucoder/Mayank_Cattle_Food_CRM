@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('styles')
    @include('weekly_report.partials.module-styles')
    @include('weekly_report.partials.confirmed-row-styles')
@endsection
@section('content')

@php
    $isRange = ($filters['mode'] ?? 'single') === 'range';
    $headerDate = $focusDate ?? now()->startOfDay();
@endphp

<div class="card mb-3 weekly-report-module">
    <div class="card-header pb-2">
        <div class="row align-items-center g-3 mb-0">
            <div class="col-12 col-lg-auto me-auto">
                <div class="d-flex align-items-center gap-2">
                    <div class="dispatch-index-icon">
                        <i class="ti ti-calendar-event"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="dispatch-index-eyebrow">Sales</div>
                        <div class="dispatch-index-title">Daily Dispatch</div>
                        <p class="text-muted small mb-0 mt-1">
                            Dispatch prediction
                            @if ($isRange)
                                — {{ count($days) }} day(s)
                                ({{ \Illuminate\Support\Carbon::parse($filters['date_from'])->format('d M Y') }}
                                – {{ \Illuminate\Support\Carbon::parse($filters['date_to'])->format('d M Y') }})
                            @else
                                — {{ $headerDate->format('d M Y') }}
                                ({{ strtoupper($headerDate->format('l')) }})
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            @if (! $isRange && $prevDate && $nextDate)
                <div class="col-12 col-lg-auto">
                    <div class="wr-header-toolbar">
                        <div class="wr-date-nav">
                            <a href="{{ route('weekly-report.index', ['date' => $prevDate]) }}"
                                class="btn btn-light btn-sm">
                                <i class="ti ti-chevron-left"></i>
                            </a>
                            <input type="text" class="form-control form-control-sm flatpickr wr-single-date-picker"
                                id="wrSingleDate" value="{{ $filters['date'] }}"
                                placeholder="DD-MM-YYYY" autocomplete="off">
                            <a href="{{ route('weekly-report.index', ['date' => $nextDate]) }}"
                                class="btn btn-light btn-sm">
                                <i class="ti ti-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <form method="get" action="{{ route('weekly-report.index') }}" id="wrFilterForm" class="mt-3 pt-3 border-top">
            <div class="cls-cardhed-part">
                <div class="cls-form-left wr-filter-bar">
                    <div class="common-hed-form cls-form-select-input">
                        <label class="col-form-label" for="filterDateFrom">From Date</label>
                        <div class="icon-form">
                            <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                            <input type="text" name="date_from" id="filterDateFrom"
                                value="{{ $filters['date_from'] ?? '' }}"
                                class="form-control flatpickr" placeholder="DD-MM-YYYY" autocomplete="off">
                        </div>
                    </div>
                    <div class="common-hed-form cls-form-select-input">
                        <label class="col-form-label" for="filterDateTo">To Date</label>
                        <div class="icon-form">
                            <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                            <input type="text" name="date_to" id="filterDateTo"
                                value="{{ $filters['date_to'] ?? '' }}"
                                class="form-control flatpickr" placeholder="DD-MM-YYYY" autocomplete="off">
                        </div>
                    </div>
                    <div class="wr-filter-actions">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="ti ti-filter me-1"></i>Apply
                        </button>
                        <a href="{{ route('weekly-report.index', ['date' => now()->toDateString()]) }}"
                            class="btn btn-danger btn-sm">
                            <i class="ti ti-refresh me-1"></i>Reset
                        </a>
                        @include('weekly_report.partials.export-print-actions', [
                            'exportQuery' => $exportQuery ?? [],
                        ])
                    </div>
                </div>
                {{-- Generate Week — hidden for now
                <div class="cls-form-right">
                    <div class="comm-header-right-btn">
                        @can('add-weekly-report')
                            <button type="button" class="btn btn-light" data-bs-toggle="collapse"
                                data-bs-target="#wrWeekGeneratePanel">
                                <i class="ti ti-calendar-plus me-1"></i>Generate Week
                            </button>
                        @endcan
                    </div>
                </div>
                --}}
            </div>
        </form>

        {{-- Generate Week panel — hidden for now
        @can('add-weekly-report')
            <div class="collapse mt-3" id="wrWeekGeneratePanel">
                <div class="card card-body border bg-light">
                    <form method="POST" action="{{ route('weekly-report.store') }}" class="row g-3 align-items-end">
                        @csrf
                        <input type="hidden" name="mode" value="week">
                        <div class="col-md-5 col-lg-4">
                            <label class="col-form-label">Any date in the week</label>
                            <input type="text" name="week_start" class="form-control flatpickr"
                                value="{{ now()->toDateString() }}" placeholder="DD-MM-YYYY" autocomplete="off" required>
                            <small class="text-muted">Creates Thu–Wed reports; existing days are skipped.</small>
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-calendar-plus me-1"></i>Generate week
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
        --}}
    </div>

    <div class="card-body pt-3">
        @forelse ($days as $day)
            @include('weekly_report.partials.day-block', [
                'day' => $day,
                'transporters' => $transporters,
                'dealers' => $dealers ?? collect(),
            ])
        @empty
            <div class="wr-empty-state">
                <i class="ti ti-calendar-off"></i>
                No dates to display. Use the filters above to select a date or range.
            </div>
        @endforelse
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
                            'idPrefix' => 'wrConfirm',
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

@endsection

@section('script')
@include('weekly_report.partials.workspace-scripts')
@include('weekly_report.partials.quick-add-scripts')
<script>
$(function () {
    flatpickr('.flatpickr', {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd-m-Y',
        allowInput: true,
    });

    $('#wrSingleDate').on('change', function () {
        var val = $(this).val();
        if (val) {
            window.location.href = "{{ route('weekly-report.index') }}?date=" + encodeURIComponent(val);
        }
    });
});
</script>
@endsection
