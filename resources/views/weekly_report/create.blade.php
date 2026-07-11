@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('content')

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div>
            <div class="dispatch-index-eyebrow">Sales</div>
            <h5 class="mb-0">Generate Weekly Report</h5>
            <p class="text-muted small mb-0 mt-1">
                Create one day report, or generate shells for a full week (Thursday → Wednesday).
                Only one report is allowed per date.
            </p>
        </div>
        <a href="{{ route('weekly-report.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="card-body">
        <!--<ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-day-btn" data-bs-toggle="tab" data-bs-target="#tab-day"
                    type="button" role="tab">Single Day</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-week-btn" data-bs-toggle="tab" data-bs-target="#tab-week"
                    type="button" role="tab">Full Week (Thu–Wed)</button>
            </li>
        </ul>-->

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-day" role="tabpanel">
                <form method="POST" action="{{ route('weekly-report.store') }}" class="row g-3" style="max-width:480px;">
                    @csrf
                    <input type="hidden" name="mode" value="day">
                    <div class="col-12">
                        <label class="col-form-label">Report Date <span class="text-danger">*</span></label>
                        <input type="date" name="report_date" class="form-control"
                            value="{{ old('report_date', now()->toDateString()) }}" required>
                        @error('report_date')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-1"></i>Create Day Report
                        </button>
                    </div>
                </form>
            </div>

            <div class="tab-pane fade" id="tab-week" role="tabpanel">
                <form method="POST" action="{{ route('weekly-report.store') }}" class="row g-3" style="max-width:480px;">
                    @csrf
                    <input type="hidden" name="mode" value="week">
                    <div class="col-12">
                        <label class="col-form-label">
                            Any date in the week <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="week_start" class="form-control"
                            value="{{ old('week_start', now()->toDateString()) }}" required>
                        <small class="text-muted">
                            Week is calculated from the Thursday on or before this date through the following Wednesday.
                            Existing day reports are skipped.
                        </small>
                        @error('week_start')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-calendar-plus me-1"></i>Generate Week Reports
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
