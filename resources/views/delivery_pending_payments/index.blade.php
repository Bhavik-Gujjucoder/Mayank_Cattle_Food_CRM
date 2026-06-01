@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('content')

<div class="delivery-pending-payments-module">

    <div class="card dpp-main-card">
        <div class="card-header">
            <div class="row align-items-center g-2 g-md-3">
                <div class="col-12 col-md-auto me-md-auto">
                    <div class="d-flex align-items-center gap-2">
                        <div class="dispatch-index-icon dpp-header-icon">
                            <i class="ti ti-report-money"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="dispatch-index-eyebrow">Sales Report</div>
                            <div class="dispatch-index-title">Dispatch Pending Payments</div>
                            <p class="text-muted small mb-0 mt-1 d-none d-sm-block">Unpaid dispatch payments after delivery</p>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-5 col-lg-4 dpp-header-filters">
                    <form method="get" action="{{ route('delivery-pending-payments.index') }}" id="dppFilterForm">
                        <select class="form-select select" name="brand_id" id="dppBrandFilter">
                            <option value="all" {{ $brandFilter === 'all' ? 'selected' : '' }}>All Brands</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}"
                                    {{ (string) $brandFilter === (string) $brand->id ? 'selected' : '' }}>
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>

                <div class="col-12 col-md-auto dpp-header-actions">
                    <a href="{{ route('delivery-pending-payments.export', ['brand_id' => $brandFilter]) }}"
                        class="btn btn-primary dpp-btn-export">
                        <i class="ti ti-file-export me-1"></i>
                        <span class="dpp-btn-label-long">Export Excel</span>
                        <span class="dpp-btn-label-short">Export</span>
                    </a>
                    <button type="button" class="btn btn-outline-secondary dpp-btn-print" onclick="window.print();">
                        <i class="ti ti-printer me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            @if ($brandSections->isEmpty())
                <div class="text-center py-5">
                    <i class="ti ti-circle-check text-success fs-1 mb-3 d-block"></i>
                    <h5 class="mb-2">No pending dispatch payments found</h5>
                    <p class="text-muted mb-0">All dispatch payments are settled, or no dispatches match your filter.</p>
                </div>
            @else
                <div class="dpp-brands-stack">
                    @foreach ($brandSections as $section)
                        @include('delivery_pending_payments.partials.brand-section', [
                            'section' => $section,
                            'canLinkOrder' => $canLinkOrder,
                        ])
                    @endforeach
                </div>
            @endif

            <div class="dpp-footnotes mt-3 pt-3 border-top small text-muted">
                <p class="mb-1 d-md-none dpp-footnote dpp-footnote--mobile">
                    <span class="dpp-footnote-label">Tip:</span>
                    Tap a day chip to see dispatch date. Only unpaid dispatches are listed.
                </p>
                @include('delivery_pending_payments.partials.footnotes-legend', [
                    'modifier' => 'd-none d-md-block d-print-block',
                ])
            </div>
        </div>
    </div>

</div>

@include('delivery_pending_payments.partials.module-responsive')

@endsection

@section('script')
<script>
$(document).ready(function () {
    $('#dppBrandFilter').select2({
        placeholder: 'Filter by brand…',
        width: '100%',
        minimumResultsForSearch: 5,
    });

    $('#dppBrandFilter').on('change', function () {
        $('#dppFilterForm').submit();
    });

    document.querySelectorAll('.delivery-pending-payments-module [data-bs-toggle="tooltip"]')
        .forEach(function (el) {
            new bootstrap.Tooltip(el, { trigger: 'hover focus' });
        });
});
</script>
@endsection
