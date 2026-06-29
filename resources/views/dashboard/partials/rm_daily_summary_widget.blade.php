@php
    $summaryDate = $rm_daily_summary['summary_date'] ?? now();
    $exportParams = array_filter([
        'rm_material_id' => $rm_material_filter !== 'all' ? $rm_material_filter : null,
        'rm_date_from' => $rm_date_from,
        'rm_date_to' => $rm_date_to,
    ]);
@endphp

@include('raw_material.partials.module-responsive')

<div class="row rm-daily-summary-module mb-4">
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

            <div class="card-body rm-summary-body">
                <div class="row g-3 mb-3 rm-summary-kpis">
                    <div class="col-6 col-md-3">
                        <div class="rm-kpi-pill">
                            <span class="rm-kpi-label">Total Ordered</span>
                            <strong id="rmKpiOrderedQty">0</strong>
                            <span class="text-muted small">tons</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="rm-kpi-pill">
                            <span class="rm-kpi-label">On Road</span>
                            <strong id="rmKpiOnRoadQty">0</strong>
                            <span class="text-muted small">tons</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="rm-kpi-pill">
                            <span class="rm-kpi-label">Unloading</span>
                            <strong id="rmKpiUnloadingQty">0</strong>
                            <span class="text-muted small">tons</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="rm-kpi-pill">
                            <span class="rm-kpi-label">Pending</span>
                            <strong id="rmKpiPendingQty">0</strong>
                            <span class="text-muted small">not on road</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 rm-summary-toolbar">
                    <p class="text-muted small mb-0">Quantities are in tons. Rate and average are per kg.</p>
                    <div id="rmSummarySearchSlot"></div>
                </div>

                <div class="table-responsive custom-table rm-summary-table-wrap">
                    <table class="table table-hover table-nowrap mb-0 dataTable no-footer dashboard-rm-summary-table w-100" id="rm_daily_summary_table">
                        <thead class="table-light">
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
                        <tfoot class="rm-summary-foot">
                            <tr class="rm-summary-footer rm-summary-footer--pending">
                                <td colspan="5"><strong>PENDING</strong></td>
                                <td class="text-end"><strong id="rmFootPendingQty">0</strong></td>
                                <td class="text-end text-muted">—</td>
                                <td class="text-end text-muted">—</td>
                                <td class="text-end text-muted">—</td>
                                <td class="text-end text-muted">—</td>
                                <td class="text-end"><strong id="rmFootPendingAvg">0.000</strong></td>
                                <td class="text-end"><strong id="rmFootPendingAmt">0.00</strong></td>
                                <td class="text-end text-muted">—</td>
                                <td class="text-end text-muted">—</td>
                            </tr>
                            <tr class="rm-summary-footer rm-summary-footer--received">
                                <td colspan="5"><strong>RECEIVED</strong> <span class="text-muted fw-normal">(without GST)</span></td>
                                <td class="text-end"><strong id="rmFootReceivedQty">0</strong></td>
                                <td class="text-end text-muted">—</td>
                                <td class="text-end text-muted">—</td>
                                <td class="text-end text-muted">—</td>
                                <td class="text-end text-muted">—</td>
                                <td class="text-end"><strong id="rmFootReceivedAvg">0.000</strong></td>
                                <td class="text-end text-muted">—</td>
                                <td class="text-end"><strong id="rmFootReceivedAmt">0.00</strong></td>
                                <td class="text-end text-muted">—</td>
                            </tr>
                            <tr class="rm-summary-footer rm-summary-footer--total">
                                <td colspan="5"><strong>TOTAL</strong></td>
                                <td class="text-end"><strong id="rmFootGrandQty">0</strong></td>
                                <td class="text-end text-muted">—</td>
                                <td class="text-end text-muted">—</td>
                                <td class="text-end text-muted">—</td>
                                <td class="text-end text-muted">—</td>
                                <td class="text-end"><strong id="rmFootGrandAvg">0.000</strong></td>
                                <td class="text-end"><strong id="rmFootGrandPendingAmt">0.00</strong></td>
                                <td class="text-end"><strong id="rmFootGrandReceivedAmt">0.00</strong></td>
                                <td class="text-end text-muted">—</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="rm-summary-dt-footer" id="rmSummaryDtFooter"></div>
            </div>
        </div>
    </div>
</div>
