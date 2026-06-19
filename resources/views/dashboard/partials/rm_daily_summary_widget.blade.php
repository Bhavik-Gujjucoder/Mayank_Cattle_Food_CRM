@php
    $summary = $rm_daily_summary;
    $rows = $summary['rows'] ?? collect();
    $totals = $summary['totals'] ?? [];
    $summaryDate = $summary['summary_date'] ?? now();
    $exportParams = array_filter([
        'rm_material_id' => $rm_material_filter !== 'all' ? $rm_material_filter : null,
        'rm_date_from' => $rm_date_from,
        'rm_date_to' => $rm_date_to,
    ]);
@endphp

@include('raw_material.partials.module-responsive')

<div class="row rm-daily-summary-module">
    <div class="col-12 d-flex">
        <div class="card flex-fill w-100 raw-material-module">
            <div class="card-header pb-2">
                <div class="row align-items-center g-3 mb-0">
                    <div class="col-12 col-sm-auto me-auto">
                        <div class="d-flex align-items-center gap-2">
                            <div class="dispatch-index-icon">
                                <i class="ti ti-report-analytics"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="dispatch-index-eyebrow">Raw Material</div>
                                <div class="dispatch-index-title">Daily Raw Material Summary</div>
                                <p class="text-muted small mb-0 mt-1">
                                    Open purchase pipeline — {{ $summaryDate->format('d M Y') }}
                                    @if ($rm_date_from || $rm_date_to)
                                        <span class="d-block d-sm-inline">
                                            (Order date:
                                            {{ $rm_date_from ? \Illuminate\Support\Carbon::parse($rm_date_from)->format('d M Y') : 'Any' }}
                                            –
                                            {{ $rm_date_to ? \Illuminate\Support\Carbon::parse($rm_date_to)->format('d M Y') : 'Any' }})
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="get" action="{{ route('dashboard') }}" id="rmDailySummaryFilterForm">
                    <div class="cls-cardhed-part mt-3 pt-3 border-top">
                        <div class="cls-form-left">
                            <div class="common-hed-form cls-form-select-input">
                                <label class="col-form-label" for="rmMaterialFilter">Material</label>
                                <select class="form-select select search-dropdown" name="rm_material_id"
                                    id="rmMaterialFilter">
                                    <option value="all" {{ $rm_material_filter === 'all' ? 'selected' : '' }}>All
                                        Materials</option>
                                    @foreach ($rm_summary_materials as $material)
                                        <option value="{{ $material->id }}"
                                            {{ (string) $rm_material_filter === (string) $material->id ? 'selected' : '' }}>
                                            {{ $material->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="common-hed-form cls-form-select-input">
                                <label class="col-form-label" for="rmDateFrom">From Date</label>
                                <div class="icon-form">
                                    <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                                    <input type="text" name="rm_date_from" id="rmDateFrom" value="{{ $rm_date_from }}"
                                        class="form-control flatpickr" placeholder="DD-MM-YYYY" autocomplete="off">
                                </div>
                            </div>
                            <div class="common-hed-form cls-form-select-input">
                                <label class="col-form-label" for="rmDateTo">To Date</label>
                                <div class="icon-form">
                                    <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                                    <input type="text" name="rm_date_to" id="rmDateTo" value="{{ $rm_date_to }}"
                                        class="form-control flatpickr" placeholder="DD-MM-YYYY" autocomplete="off">
                                </div>
                            </div>
                            <div class="common-hed-form cls-form-select-input d-flex align-items-end">
                                <a href="{{ route('dashboard') }}" class="btn btn-danger" id="rmResetSummaryFilters">
                                    <i class="ti ti-refresh me-1"></i>Reset
                                </a>
                            </div>
                        </div>
                        <div class="cls-form-right">
                            <div class="comm-header-right-btn">
                                @can('view-raw-material-purchas-order')
                                    <a href="{{ route('raw-material.order.index') }}" class="btn btn-light btn-md">
                                        <i class="ti ti-list me-1"></i>View All
                                    </a>
                                @endcan
                                @can('export-raw-material-purchas-order')
                                    <a href="{{ route('dashboard.raw-material-daily-summary.export', $exportParams) }}"
                                        class="btn btn-outline-primary" id="rmDailySummaryExportBtn">
                                        <i class="ti ti-file-export me-2"></i>Export Excel
                                    </a>
                                @endcan
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body">
                <div class="row g-2 mb-3 rm-summary-kpis">
                    <div class="col-6 col-md-3">
                        <div class="rm-kpi-pill">
                            <span class="rm-kpi-label">Total Ordered</span>
                            <strong>{{ number_format($totals['ordered_qty'] ?? 0) }}</strong>
                            <span class="text-muted small">tons</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="rm-kpi-pill">
                            <span class="rm-kpi-label">On Road</span>
                            <strong>{{ number_format($totals['on_road_qty'] ?? 0) }}</strong>
                            <span class="text-muted small">tons</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="rm-kpi-pill">
                            <span class="rm-kpi-label">Unloading</span>
                            <strong>{{ number_format($totals['unloading_qty'] ?? 0) }}</strong>
                            <span class="text-muted small">tons</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="rm-kpi-pill">
                            <span class="rm-kpi-label">Pending</span>
                            <strong>{{ number_format($totals['pending_not_on_road'] ?? 0) }}</strong>
                            <span class="text-muted small">not on road</span>
                        </div>
                    </div>
                </div>

                @if ($rows->isEmpty())
                    <div class="text-center py-5">
                        <i class="ti ti-package-off text-muted fs-1 mb-3 d-block"></i>
                        <h5 class="mb-2">No open raw material orders found</h5>
                        <p class="text-muted mb-0">All purchase orders are fully received or no items match your filters.</p>
                    </div>
                @else
                    <div class="table-responsive custom-table rm-summary-table-wrap">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Sr</th>
                                    <th>Date</th>
                                    <th>Supplier Broker</th>
                                    <th>Party Name</th>
                                    <th>Material</th>
                                    <th class="text-end">Total Qty</th>
                                    <th class="text-end">On Road</th>
                                    <th class="text-end">Unloading</th>
                                    <th class="text-end">Pending</th>
                                    <th class="text-end">Rate</th>
                                    <th class="text-end">Avg</th>
                                    <th class="text-end">Pending Amt</th>
                                    <th class="text-end">Received Amt</th>
                                    <th class="text-end">Freight</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $index => $row)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $row['order_date'] }}</td>
                                        <td>{{ $row['supplier_broker_name'] }}</td>
                                        <td>
                                            @can('view-raw-material-purchas-order')
                                                @if ($row['order_id'] > 0)
                                                    <a href="{{ route('raw-material.order.show', $row['order_id']) }}"
                                                        class="text-decoration-none">
                                                        {{ $row['party_name'] }}
                                                    </a>
                                                @else
                                                    {{ $row['party_name'] }}
                                                @endif
                                            @else
                                                {{ $row['party_name'] }}
                                            @endcan
                                        </td>
                                        <td>{{ $row['material_name'] }}</td>
                                        <td class="text-end">{{ number_format($row['total_qty']) }}</td>
                                        <td class="text-end">{{ number_format($row['on_road_qty']) }}</td>
                                        <td class="text-end">{{ number_format($row['unloading_qty']) }}</td>
                                        <td class="text-end">{{ number_format($row['pending_qty']) }}</td>
                                        <td class="text-end">{{ number_format($row['rate'], 2) }}</td>
                                        <td class="text-end">{{ number_format($row['average'], 2) }}</td>
                                        <td class="text-end">{{ number_format($row['pending_amount'], 2) }}</td>
                                        <td class="text-end">{{ number_format($row['received_amount'], 2) }}</td>
                                        <td class="text-end">{{ number_format($row['freight'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="rm-summary-footer rm-summary-footer--pending">
                                    <td colspan="5"><strong>PENDING</strong></td>
                                    <td class="text-end"><strong>{{ number_format($totals['pending']['qty'] ?? 0) }}</strong>
                                    </td>
                                    <td colspan="4"></td>
                                    <td class="text-end">
                                        <strong>{{ number_format($totals['pending']['average'] ?? 0, 3) }}</strong></td>
                                    <td class="text-end">
                                        <strong>{{ number_format($totals['pending']['amount'] ?? 0, 2) }}</strong></td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr class="rm-summary-footer rm-summary-footer--received">
                                    <td colspan="5"><strong>RECEIVED</strong> <span class="text-muted fw-normal">(without
                                            GST)</span></td>
                                    <td class="text-end">
                                        <strong>{{ number_format($totals['received']['qty'] ?? 0) }}</strong></td>
                                    <td colspan="4"></td>
                                    <td class="text-end">
                                        <strong>{{ number_format($totals['received']['average'] ?? 0, 3) }}</strong></td>
                                    <td></td>
                                    <td class="text-end">
                                        <strong>{{ number_format($totals['received']['amount'] ?? 0, 2) }}</strong></td>
                                    <td></td>
                                </tr>
                                <tr class="rm-summary-footer rm-summary-footer--total">
                                    <td colspan="5"><strong>TOTAL</strong></td>
                                    <td class="text-end"><strong>{{ number_format($totals['grand']['qty'] ?? 0) }}</strong>
                                    </td>
                                    <td colspan="4"></td>
                                    <td class="text-end">
                                        <strong>{{ number_format($totals['grand']['average'] ?? 0, 3) }}</strong></td>
                                    <td class="text-end">
                                        <strong>{{ number_format($totals['pending']['amount'] ?? 0, 2) }}</strong></td>
                                    <td class="text-end">
                                        <strong>{{ number_format($totals['received']['amount'] ?? 0, 2) }}</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Quantities are in tons. Rate and average are per kg.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .rm-daily-summary-module .rm-kpi-pill {
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        height: 100%;
    }

    .rm-daily-summary-module .rm-kpi-label {
        display: block;
        font-size: 0.75rem;
        color: #64748b;
        margin-bottom: 0.25rem;
    }

    .rm-daily-summary-module .rm-summary-table-wrap {
        max-height: 28rem;
        overflow: auto;
    }

    .rm-daily-summary-module .rm-summary-footer td {
        background: #f8fafc;
    }

    .rm-daily-summary-module .rm-summary-footer--pending td:first-child {
        border-left: 3px solid #f59e0b;
    }

    .rm-daily-summary-module .rm-summary-footer--received td:first-child {
        border-left: 3px solid #10b981;
    }

    .rm-daily-summary-module .rm-summary-footer--total td:first-child {
        border-left: 3px solid #3b82f6;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var $form = $('#rmDailySummaryFilterForm');

        $('#rmMaterialFilter').on('change', function() {
            $form.trigger('submit');
        });

        if (typeof flatpickr !== 'undefined') {
            flatpickr('#rmDateFrom', {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd-m-Y',
                allowInput: true,
                defaultDate: @json($rm_date_from),
                onChange: function() {
                    $form.trigger('submit');
                },
            });

            flatpickr('#rmDateTo', {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd-m-Y',
                allowInput: true,
                defaultDate: @json($rm_date_to),
                onChange: function() {
                    $form.trigger('submit');
                },
            });
        }
    });
</script>
