@php
    use App\Models\WeeklyReportItem;
    use App\Support\ProductUnit;

    $report = $day['report'] ?? null;
    $dayDate = $day['date'];
    $blockId = 'wr-day-' . $dayDate->format('Ymd');
    $canEdit = auth()->user()->can('edit-weekly-report');
    $canDelete = auth()->user()->can('delete-weekly-report');
    $canConfirm = $canEdit && auth()->user()->can('add-dispatch');
    $canAdd = auth()->user()->can('edit-weekly-report') && $report;
    $totalBags = $report ? $report->totalQuantityInBags() : 0;
    $difference = $report ? $report->differenceInBags() : 0;
    $hours = $report ? $report->productionHours() : 0;
@endphp

<div class="wr-day-section weekly-report-day-block"
    id="{{ $blockId }}"
    data-report-id="{{ $report?->id }}"
    data-report-date="{{ $dayDate->toDateString() }}">

    <div class="wr-day-header">
        <h5 class="wr-day-date">
            {{ $dayDate->format('d M Y') }} — {{ strtoupper($dayDate->format('l')) }}
        </h5>
        <p class="wr-day-hint">
            Pick pending orders below, then confirm each row to create a dispatch.
        </p>
    </div>

    <div class="wr-day-body">
        @if (! $report)
            <div class="alert alert-light border mb-0 d-flex flex-wrap align-items-center gap-2">
                <i class="ti ti-info-circle text-primary"></i>
                <span>No report exists for this date yet.</span>
                @can('add-weekly-report')
                    <form method="POST" action="{{ route('weekly-report.store') }}" class="d-inline ms-auto">
                        @csrf
                        <input type="hidden" name="mode" value="day">
                        <input type="hidden" name="report_date" value="{{ $dayDate->toDateString() }}">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="ti ti-calendar-plus me-1"></i>Create report
                        </button>
                    </form>
                @endcan
            </div>
        @else
            @if ($canAdd)
                @include('weekly_report.partials.add-row-form', [
                    'report' => $report,
                    'transporters' => $transporters,
                    'blockId' => $blockId,
                ])
            @endif

            <div class="wr-section-label">
                <i class="ti ti-list-details"></i> Planned orders
                <span class="wr-section-count">({{ $report->items->count() }})</span>
            </div>

            @include('weekly_report.partials.items-table', [
                'report' => $report,
                'transporters' => $transporters,
                'canEdit' => $canEdit,
                'canDelete' => $canDelete,
                'canConfirm' => $canConfirm,
                'blockId' => $blockId,
            ])

            @include('weekly_report.partials.production-footer', [
                'report' => $report,
                'canEdit' => $canEdit,
                'totalBags' => $totalBags,
                'difference' => $difference,
                'hours' => $hours,
                'blockId' => $blockId,
            ])
        @endif
    </div>
</div>
