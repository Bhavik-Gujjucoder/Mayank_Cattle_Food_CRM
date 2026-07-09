@extends('layouts.main')

@section('styles')
<style>
    .recent-cards .custom-table .dataTables_wrapper {
        padding: 0;
    }

    .recent-cards .custom-table table.dashboard-recent-table,
    .recent-cards .custom-table table.dashboard-recent-table.dataTable {
        width: 100% !important;
        margin-bottom: 0;
        border: none !important;
        border-collapse: separate !important;
        border-spacing: 0;
    }

    .recent-cards .custom-table table.dashboard-recent-table thead {
        display: table-header-group !important;
    }

    .recent-cards .custom-table table.dashboard-recent-table thead th {
        font-size: 13px;
        font-weight: 500;
        color: #262A2A;
        background-color: #f8f9fa;
        border-bottom: 1px solid #E8E8E8;
        padding: 10px 15px;
        white-space: nowrap;
        vertical-align: middle;
    }

    .recent-cards .custom-table table.dashboard-recent-table tbody td {
        display: table-cell !important;
        font-size: 13px;
        color: #6F6F6F;
        border-bottom: 1px solid #E8E8E8;
        padding: 10px 15px;
        vertical-align: middle;
        white-space: nowrap;
    }

    .recent-cards .custom-table table.dashboard-recent-table tbody tr:last-child td {
        border-bottom: none;
    }

    .recent-cards .custom-table table.dashboard-recent-table tbody tr:hover td {
        background-color: rgba(0, 0, 0, 0.02);
    }

    .recent-cards .custom-table .dataTables_empty {
        padding: 12px 15px;
        text-align: center;
        border-bottom: none;
    }

    .rm-daily-summary-module #rm_daily_summary_table,
    .rm-daily-summary-module #rm_daily_summary_table.dataTable,
    .rm-daily-summary-module table.dashboard-rm-summary-table {
        width: 100% !important;
        margin-bottom: 0;
        border: none !important;
        border-collapse: separate !important;
        border-spacing: 0;
    }

    .rm-daily-summary-module .rm-summary-body {
        padding: 1rem 1.25rem 1.25rem;
    }

    .rm-daily-summary-module .rm-kpi-pill {
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 0.85rem 1rem;
        height: 100%;
        background: #fff;
    }

    .rm-daily-summary-module .rm-kpi-label {
        display: block;
        font-size: 12px;
        color: #64748b;
        margin-bottom: 0.35rem;
    }

    .rm-daily-summary-module .rm-kpi-pill strong {
        font-size: 1.25rem;
        font-weight: 700;
        color: #262A2A;
    }

    .rm-daily-summary-module .custom-table .dataTables_wrapper {
        padding: 0;
    }

    .rm-daily-summary-module table.dashboard-rm-summary-table thead th {
        font-size: 13px;
        font-weight: 500;
        color: #262A2A;
        background-color: #f8f9fa;
        border-bottom: 1px solid #E8E8E8;
        border-top: none;
        border-left: none;
        border-right: none;
        padding: 10px 15px;
        white-space: nowrap;
        vertical-align: middle;
    }

    .rm-daily-summary-module table.dashboard-rm-summary-table tbody td {
        font-size: 13px;
        color: #6F6F6F;
        border-bottom: 1px solid #E8E8E8;
        border-left: none;
        border-right: none;
        border-top: none;
        padding: 10px 15px;
        vertical-align: middle;
    }

    .rm-daily-summary-module table.dashboard-rm-summary-table tbody tr:last-child td {
        border-bottom: none;
    }

    .rm-daily-summary-module table.dashboard-rm-summary-table tbody tr:hover td {
        background-color: rgba(0, 0, 0, 0.02);
    }

    .rm-daily-summary-module table.dashboard-rm-summary-table tfoot td {
        background: #f8fafc;
        border-top: 1px solid #E8E8E8;
        border-bottom: none !important;
        border-left: none !important;
        border-right: none !important;
        padding: 10px 15px;
        font-size: 13px;
        color: #262A2A;
        vertical-align: middle;
    }

    .rm-daily-summary-module table.dashboard-rm-summary-table tfoot tr.rm-summary-footer--pending td:first-child {
        box-shadow: inset 3px 0 0 #f59e0b;
    }

    .rm-daily-summary-module table.dashboard-rm-summary-table tfoot tr.rm-summary-footer--received td:first-child {
        box-shadow: inset 3px 0 0 #10b981;
    }

    .rm-daily-summary-module table.dashboard-rm-summary-table tfoot tr.rm-summary-footer--total td:first-child {
        box-shadow: inset 3px 0 0 #3b82f6;
    }

    .rm-daily-summary-module table.dashboard-rm-summary-table tfoot tr:last-child td {
        border-bottom: none !important;
    }

    .rm-daily-summary-module .rm-summary-table-wrap {
        max-height: 28rem;
        overflow: auto;
        border-radius: 0;
    }

    .rm-daily-summary-module #rmSummarySearchSlot .dataTables_filter {
        margin: 0;
        float: none;
        text-align: right;
    }

    .rm-daily-summary-module #rmSummarySearchSlot .dataTables_filter label {
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0;
    }

    .rm-daily-summary-module #rmSummarySearchSlot .dataTables_filter input {
        margin: 0 !important;
        width: 220px;
        font-size: 13px;
        padding: 0.4rem 0.75rem;
        border: 1px solid #E8E8E8;
        border-radius: 0.375rem;
    }

    .rm-daily-summary-module .rm-summary-dt-footer {
        margin-top: 0.75rem;
    }

    .rm-daily-summary-module .rm-summary-dt-footer .dataTables_info,
    .rm-daily-summary-module .rm-summary-dt-footer .dataTables_paginate {
        margin: 0;
        padding: 0;
        font-size: 13px;
        color: #6F6F6F;
    }

    .rm-daily-summary-module .rm-summary-dt-footer .dataTables_paginate .pagination {
        margin-bottom: 0;
        justify-content: flex-end;
    }

    .rm-daily-summary-module .dataTables_empty {
        padding: 12px 15px;
        text-align: center;
        border-bottom: none;
    }
</style>
@include('weekly_report.partials.confirmed-row-styles')
@endsection

@section('content')
@section('title')
    <h3>{{ $page_title }}</h3>
@endsection
<!-- Welcome + quick actions -->
<div class="mb-4">
    <div class="welcome-wrap">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
            <div class="mb-3">
                <h2 class="mb-1 text-white">Welcome Back, {{ $user_name }}</h2>
                <p class="text-light"></p>
            </div>
        </div>
    </div>
    @can('add-order')
        <div class="d-flex justify-content-end mt-3">
            <a href="javascript:void(0)" class="btn btn-primary btn-md" data-bs-toggle="modal"
                data-bs-target="#dashboardDispatchModal">
                <i class="ti ti-truck me-1"></i>Soda/Order Dispatch
            </a>
        </div>
    @endcan
</div>
<div class="row detials-gc-user">

    @can('total-dealers')
        <div class="col-xl-3 col-sm-6 d-flex">
            <div class="card flex-fill total-dealers">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="avatar avatar-md rounded bg-dark mb-3">
                            <i class="ti ti-medal fs-16"></i>
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="mb-1">{{ $total_dealers }}</h2>
                            <p class="fs-13">Total Dealers</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    @can('total-sales-brokers')
        <div class="col-xl-3 col-sm-6 d-flex">
            <div class="card flex-fill total-broker">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="avatar avatar-md rounded bg-dark mb-3">
                            <i class="ti ti-user-up fs-16"></i>
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="mb-1">{{ $total_broker }}</h2>
                            <p class="fs-13">Total Sales Broker</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    @can('total-soda-order')
        <div class="col-xl-3 col-sm-6 d-flex">
            <div class="card flex-fill total-soda-order">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="avatar avatar-md rounded bg-dark mb-3">
                            <i class="ti ti-user-star fs-16"></i>
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="mb-1">{{ $total_soda_order }}</h2>
                            <p class="fs-13">Total Soda/Order</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    @can('total-dispatch-request')
        <div class="col-xl-3 col-sm-6 d-flex">
            <div class="card flex-fill total-dispatch-request">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="avatar avatar-md rounded bg-dark mb-3">
                            <i class="ti ti-businessplan fs-16"></i>
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="mb-1">{{ $total_dispatch_order }}</h2>
                            <p class="fs-13">Total Dispatch request</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    <!------------ Total Raw Materials ---------------->
    @can('total-raw-materials')
        <div class="col-xl-3 col-sm-6 d-flex">
            <div class="card flex-fill total-raw-materials">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="avatar avatar-md rounded bg-dark mb-3">
                            <i class="ti ti-package fs-16"></i>
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="mb-1">{{ $total_raw_materials }}</h2>
                            <p class="fs-13">Total Raw Materials</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    <!------------ Total Raw Material Orders ---------------->
    @can('total-raw-material-orders')
        <div class="col-xl-3 col-sm-6 d-flex">
            <div class="card flex-fill total-raw-material-orders">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="avatar avatar-md rounded bg-dark mb-3">
                            <i class="ti ti-clipboard-list fs-16"></i>
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="mb-1">{{ $total_raw_material_orders }}</h2>
                            <p class="fs-13">Total Raw Material Orders</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan
</div>
{{-- {{dd(Auth::user()->getPermissionsViaRoles())}} --}}
@can('view-weekly-report')
    @include('dashboard.partials.current_day_report_widget')
@endcan

@can('raw-material-daily-summary')
    @if ($rm_daily_summary)
        @include('dashboard.partials.rm_daily_summary_widget')
    @endif
@endcan

@push('datatable-scripts')
    @include('partials.datatable-scripts')
@endpush

<div class="row">
    @can('recent-dealers')
        <div class="col-xxl-4 col-xl-6 d-flex">
            <div class="card flex-fill recent-cards w-100">
                <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                    <h5 class="mb-2">Recent Dealers</h5>
                    <a href="{{ route('dealer.index') }}" class="btn btn-light btn-md mb-2">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive custom-table">
                        <table class="table table-hover table-nowrap mb-0 dataTable no-footer dashboard-recent-table w-100" id="dashboard_dealers_table">
                            <thead class="table-light">
                                <tr>
                                    <th>Sr</th>
                                    <th>Dealer</th>
                                    <th>City</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    @can('recent-soda-orders')
        <div class="col-xxl-4 col-xl-6 d-flex">
            <div class="card flex-fill recent-cards w-100">
                <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="mb-2">Recent Soda/Orders</h5>
                    <a href="{{ route('order.index') }}" class="btn btn-light btn-md mb-2">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive custom-table">
                        <table class="table table-hover table-nowrap mb-0 dataTable no-footer dashboard-recent-table w-100" id="dashboard_soda_orders_table">
                            <thead class="table-light">
                                <tr>
                                    <th>Sr</th>
                                    <th>Order ID</th>
                                    <th>Dealer</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    @can('recent-dispatch-request')
        <div class="col-xxl-4 col-xl-6 d-flex">
            <div class="card flex-fill recent-cards w-100">
                <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                    <h5 class="mb-2">Recent Dispatch Request</h5>
                    <a href="{{ route('dispatch.index') }}" class="btn btn-light btn-md mb-2">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive custom-table">
                        <table class="table table-hover table-nowrap mb-0 dataTable no-footer dashboard-recent-table w-100" id="dashboard_dispatches_table">
                            <thead class="table-light">
                                <tr>
                                    <th>Sr</th>
                                    <th>Product</th>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endcan
</div>

<div class="row">
    @can('raw-material-orders')
        <div class="col-xxl-12 col-xl-12 d-flex">
            <div class="card flex-fill recent-cards w-100">
                <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                    <h5 class="mb-8">Raw Material Orders</h5>
                    <a href="{{ route('raw-material.order.index') }}" class="btn btn-light btn-md mb-2">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive custom-table">
                        <table class="table table-hover table-nowrap mb-0 dataTable no-footer dashboard-recent-table w-100" id="dashboard_rm_orders_table">
                            <thead class="table-light">
                                <tr>
                                    <th>Sr</th>
                                    <th>Order ID</th>
                                    <th>Supplier Broker</th>
                                    <th>Supplier</th>
                                    <th>Order Date</th>
                                    <th class="text-end">Total Qty</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    @can('raw-material-received-onroad')
        <div class="col-xxl-12 col-xl-12 d-flex">
            <div class="card flex-fill recent-cards w-100">
                <div class="card-header pb-2 d-flex align-items-center justify-content-between flex-wrap">
                    <h5 class="mb-8">Raw Material Received OnRoad</h5>
                    <a href="{{ route('raw-material.receive.index') }}" class="btn btn-light btn-md mb-2">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive custom-table">
                        <table class="table table-hover table-nowrap mb-0 dataTable no-footer dashboard-recent-table w-100" id="dashboard_rm_receives_table">
                            <thead class="table-light">
                                <tr>
                                    <th>Sr</th>
                                    <th>Order ID</th>
                                    <th>Supplier Order ID</th>
                                    <th>Category</th>
                                    <th>Material</th>
                                    <th class="text-end">Qty (tons)</th>
                                    <th>Freight</th>
                                    <th>Received Date</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endcan
</div>

@can('add-dispatch')
    @include('dispatch_management.partials.dashboard_dispatch_modal')
@endcan

@endsection
@section('script')
<script>
withDataTable(function($) {
    var dashboardRecentDtDefaults = {
        pageLength: 10,
        deferRender: true,
        processing: true,
        serverSide: false,
        paging: false,
        info: false,
        lengthChange: false,
        searching: false,
        ordering: false,
        responsive: false,
        autoWidth: false,
        order: [],
        dom: 'rt',
        language: {
            emptyTable: 'No records found.',
            loadingRecords: '',
            processing: ''
        }
    };

    function initDashboardRecentTable(selector, url, columns) {
        if (!$(selector).length) {
            return null;
        }

        var ajaxConfig = buildDataTableAjax(url);
        var table = $(selector).DataTable($.extend(true, {}, dashboardRecentDtDefaults, {
            ajax: ajaxConfig,
            columns: columns,
            drawCallback: function() {
                $(selector).addClass('dashboard-recent-table w-100');
            }
        }));
        ajaxConfig._bindTable(table);

        return table;
    }

    @can('recent-dealers')
    initDashboardRecentTable('#dashboard_dealers_table', @json(route('dashboard.data.dealers')), [
        { data: 'DT_RowIndex', title: 'Sr', name: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'dealer_name', title: 'Dealer', name: 'dealer_name', orderable: false },
        { data: 'city_name', title: 'City', name: 'city_name', orderable: false }
    ]);
    @endcan

    @can('recent-soda-orders')
    initDashboardRecentTable('#dashboard_soda_orders_table', @json(route('dashboard.data.soda-orders')), [
        { data: 'DT_RowIndex', title: 'Sr', name: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'order_ref', title: 'Order ID', name: 'unique_order_id', orderable: false },
        { data: 'dealer_name', title: 'Dealer', name: 'dealer_name', orderable: false },
        { data: 'order_date', title: 'Date', name: 'order_date', orderable: false }
    ]);
    @endcan

    @can('recent-dispatch-request')
    initDashboardRecentTable('#dashboard_dispatches_table', @json(route('dashboard.data.dispatches')), [
        { data: 'DT_RowIndex', title: 'Sr', name: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'product_info', title: 'Product', name: 'product_info', orderable: false },
        { data: 'order_ref', title: 'Order ID', name: 'order_ref', orderable: false },
        { data: 'dispatch_date', title: 'Date', name: 'dispatch_date', orderable: false }
    ]);
    @endcan

    @can('raw-material-orders')
    initDashboardRecentTable('#dashboard_rm_orders_table', @json(route('dashboard.data.raw-material-orders')), [
        { data: 'DT_RowIndex', title: 'Sr', name: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'order_ref', title: 'Order ID', name: 'order_unique_id', orderable: false },
        { data: 'supplier_broker_name', title: 'Supplier Broker', name: 'supplier_broker_name', orderable: false },
        { data: 'supplier_name', title: 'Supplier', name: 'supplier_name', orderable: false },
        { data: 'order_date', title: 'Order Date', name: 'order_date', orderable: false },
        { data: 'total_qty', title: 'Total Qty', name: 'total_qty', orderable: false, className: 'text-end' }
    ]);
    @endcan

    @can('raw-material-received-onroad')
    initDashboardRecentTable('#dashboard_rm_receives_table', @json(route('dashboard.data.raw-material-receives')), [
        { data: 'DT_RowIndex', title: 'Sr', name: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'order_ref', title: 'Order ID', name: 'order_ref', orderable: false },
        { data: 'supplier_order_id', title: 'Supplier Order ID', name: 'supplier_order_id', orderable: false },
        { data: 'category_name', title: 'Category', name: 'category_name', orderable: false },
        { data: 'material_name', title: 'Material', name: 'material_name', orderable: false },
        { data: 'qty', title: 'Qty (tons)', name: 'qty', orderable: false, className: 'text-end' },
        { data: 'freight_html', title: 'Freight', name: 'freight_html', orderable: false },
        { data: 'received_date', title: 'Received Date', name: 'received_date', orderable: false }
    ]);
    @endcan

    @can('raw-material-daily-summary')
    @if ($rm_daily_summary)
    function updateRmSummaryTotals(totals) {
        totals = totals || {};
        var top = totals || {};
        $('#rmKpiOrderedQty').text(Number(top.ordered_qty || 0).toLocaleString());
        $('#rmKpiOnRoadQty').text(Number(top.on_road_qty || 0).toLocaleString());
        $('#rmKpiUnloadingQty').text(Number(top.unloading_qty || 0).toLocaleString());
        $('#rmKpiPendingQty').text(Number(top.pending_not_on_road || 0).toLocaleString());

        var pending = top.pending || {};
        var received = top.received || {};
        var grand = top.grand || {};

        $('#rmFootPendingQty').text(Number(pending.qty || 0).toLocaleString());
        $('#rmFootPendingAvg').text(Number(pending.average || 0).toFixed(3));
        $('#rmFootPendingAmt').text(Number(pending.amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        $('#rmFootReceivedQty').text(Number(received.qty || 0).toLocaleString());
        $('#rmFootReceivedAvg').text(Number(received.average || 0).toFixed(3));
        $('#rmFootReceivedAmt').text(Number(received.amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        $('#rmFootGrandQty').text(Number(grand.qty || 0).toLocaleString());
        $('#rmFootGrandAvg').text(Number(grand.average || 0).toFixed(3));
        $('#rmFootGrandPendingAmt').text(Number(pending.amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#rmFootGrandReceivedAmt').text(Number(received.amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    }

    var rmSummaryAjax = buildDataTableAjax(@json(route('dashboard.data.rm-daily-summary')), {
        data: function(d) {
            d.rm_material_id = $('#rmMaterialFilter').val() || 'all';
            d.rm_date_from = $('#rmDateFrom').val() || '';
            d.rm_date_to = $('#rmDateTo').val() || '';
        }
    });

    var dashboardSummaryDtDefaults = {
        pageLength: 25,
        deferRender: true,
        processing: true,
        serverSide: true,
        lengthChange: false,
        searching: true,
        ordering: true,
        responsive: false,
        autoWidth: false,
        order: [],
        dom: 'rt<"rm-summary-dt-footer row align-items-center g-2"<"col-sm-6"i><"col-sm-6"p>>',
        language: {
            emptyTable: 'No records found.',
            loadingRecords: '',
            processing: '',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: 'No entries to show',
            paginate: {
                previous: 'Previous',
                next: 'Next'
            }
        }
    };

    var rmSummaryTable = $('#rm_daily_summary_table').DataTable($.extend(true, {}, dashboardSummaryDtDefaults, {
        ajax: $.extend(true, {}, rmSummaryAjax, {
            dataSrc: function(json) {
                if (json.totals) {
                    updateRmSummaryTotals(json.totals);
                }
                return json.data;
            }
        }),
        initComplete: function() {
            var $filter = $('#rm_daily_summary_table_filter');
            if ($filter.length) {
                $filter.appendTo('#rmSummarySearchSlot');
                $filter.find('input').attr('placeholder', 'Search summary...');
            }

            var $footer = $('#rm_daily_summary_table_wrapper .rm-summary-dt-footer');
            if ($footer.length) {
                $footer.appendTo('#rmSummaryDtFooter');
            }

            $('#rm_daily_summary_table').addClass('dashboard-rm-summary-table w-100');
        },
        columns: [
            { data: 'DT_RowIndex', title: 'Sr', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'order_date', title: 'Date', name: 'order_date' },
            { data: 'supplier_broker_name', title: 'Supplier Broker', name: 'supplier_broker_name' },
            { data: 'party_name', title: 'Party Name', name: 'party_name' },
            { data: 'material_name', title: 'Material', name: 'material_name' },
            { data: 'total_qty_fmt', title: 'Total Qty', name: 'total_qty', className: 'text-end' },
            { data: 'on_road_qty_fmt', title: 'On Road', name: 'on_road_qty', className: 'text-end' },
            { data: 'unloading_qty_fmt', title: 'Unloading', name: 'unloading_qty', className: 'text-end' },
            { data: 'pending_qty_fmt', title: 'Pending', name: 'pending_qty', className: 'text-end' },
            { data: 'rate_fmt', title: 'Rate', name: 'rate', className: 'text-end' },
            { data: 'average_fmt', title: 'Avg', name: 'average', className: 'text-end' },
            { data: 'pending_amount_fmt', title: 'Pending Amt', name: 'pending_amount', className: 'text-end' },
            { data: 'received_amount_fmt', title: 'Received Amt', name: 'received_amount', className: 'text-end' },
            { data: 'freight_fmt', title: 'Freight', name: 'freight', className: 'text-end' }
        ]
    }));
    rmSummaryAjax._bindTable(rmSummaryTable);

    function updateRmExportLink() {
        var params = new URLSearchParams();
        var material = $('#rmMaterialFilter').val();
        if (material && material !== 'all') {
            params.set('rm_material_id', material);
        }
        var from = $('#rmDateFrom').val();
        if (from) {
            params.set('rm_date_from', from);
        }
        var to = $('#rmDateTo').val();
        if (to) {
            params.set('rm_date_to', to);
        }
        var base = @json(route('dashboard.raw-material-daily-summary.export'));
        var qs = params.toString();
        $('#rmDailySummaryExportBtn').attr('href', qs ? base + '?' + qs : base);
    }

    var $form = $('#rmDailySummaryFilterForm');
    $('#rmMaterialFilter').on('change', function() {
        updateRmExportLink();
        rmSummaryTable.ajax.reload();
    });

    if (typeof flatpickr !== 'undefined') {
        flatpickr('#rmDateFrom', {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd-m-Y',
            allowInput: true,
            defaultDate: @json($rm_date_from),
            onChange: function() {
                updateRmExportLink();
                rmSummaryTable.ajax.reload();
            },
        });

        flatpickr('#rmDateTo', {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd-m-Y',
            allowInput: true,
            defaultDate: @json($rm_date_to),
            onChange: function() {
                updateRmExportLink();
                rmSummaryTable.ajax.reload();
            },
        });
    }

    updateRmExportLink();
    @endif
    @endcan
});
</script>

@can('add-dispatch')
    @include('dispatch_management.partials.status-field-script')
    <script>
        $(document).ready(function() {
            var TRUCKS_URL = '{{ route('dispatch.transporterTrucks', ':id') }}';
            var ORDERS_URL = @json(route('dashboard.data.dispatch-form-orders'));
            var dashboardDispatchEligible = true;
            var dispatchOrdersLoaded = false;

            function loadDispatchOrders(callback) {
                var $sel = $('#dashboardDispatchOrderId');
                if (dispatchOrdersLoaded) {
                    if (typeof callback === 'function') {
                        callback();
                    }
                    return;
                }

                $sel.prop('disabled', true).html('<option value="">Loading orders…</option>');

                $.get(ORDERS_URL)
                    .done(function(data) {
                        var html = '<option value="">-- Select Order --</option>';
                        var savedOrder = @json(old('order_id'));
                        $.each(data.orders || [], function(i, order) {
                            var selected = String(savedOrder) === String(order.id) ? ' selected' : '';
                            html += '<option value="' + order.id + '" data-form-url="' + order.form_url + '"' + selected + '>' +
                                $('<span>').text(order.label).html() + '</option>';
                        });
                        $sel.html(html).prop('disabled', false);
                        dispatchOrdersLoaded = true;
                        if (typeof callback === 'function') {
                            callback();
                        }
                    })
                    .fail(function() {
                        $sel.html('<option value="">Could not load orders</option>').prop('disabled', false);
                    });
            }

            function loadTrucksForTransporter(transporterId, $truckSelect, $contactInput, opts) {
                opts = opts || {};
                if (!transporterId) {
                    $truckSelect.html('<option value="">-- Select Transporter First --</option>').prop('disabled',
                        true);
                    return;
                }
                $truckSelect.html('<option value="">Loading trucks…</option>').prop('disabled', true);
                $.get(TRUCKS_URL.replace(':id', transporterId))
                    .done(function(data) {
                        var html = '<option value="">-- Select Truck Number --</option>';
                        if (data.trucks && data.trucks.length) {
                            $.each(data.trucks, function(i, truck) {
                                html += '<option value="' + $('<span>').text(truck.truck_number)
                                    .html() + '">' +
                                    $('<span>').text(truck.truck_number).html() + '</option>';
                            });
                        } else {
                            html += '<option value="" disabled>No trucks found for this transporter</option>';
                        }
                        $truckSelect.html(html).prop('disabled', false);
                        if (opts.setTruckNumber) {
                            if ($truckSelect.find('option[value="' + opts.setTruckNumber + '"]').length === 0) {
                                $truckSelect.append('<option value="' + $('<span>').text(opts.setTruckNumber)
                                    .html() + '">' + $('<span>').text(opts.setTruckNumber).html() +
                                    '</option>');
                            }
                            $truckSelect.val(opts.setTruckNumber);
                        }
                        if (opts.setDriverContact !== undefined && opts.setDriverContact !== null) {
                            $contactInput.val(opts.setDriverContact);
                        } else if (opts.autoFillContact && data.phone) {
                            $contactInput.val(data.phone);
                        }
                    })
                    .fail(function() {
                        $truckSelect.html('<option value="">-- Select Truck Number --</option>').prop(
                            'disabled', false);
                    });
            }

            function resetProductSelect(message) {
                $('#dashboardDispatchOrderItemId')
                    .html('<option value="">' + (message || '-- Select Order First --') + '</option>')
                    .prop('disabled', true)
                    .val('');
                $('#dashboardDispatchProductId').val('');
                $('#dashboardDispatchPendingHint').text('');
            }

            function updateDashboardQtyLabel(unit) {
                var label = unit ? ('No of ' + unit) : @json(\App\Support\ProductUnit::quantityFieldLabel());
                $('#dashboardDispatchQtyLabel').text(label);
            }

            function populateProductSelect(items) {
                var $sel = $('#dashboardDispatchOrderItemId');
                var html = '<option value="">-- Select Product --</option>';
                $.each(items, function(i, item) {
                    var disabled = item.disabled ? ' disabled' : '';
                    html += '<option value="' + item.id + '" data-product-id="' + item.product_id +
                        '" data-product-unit="' + (item.product_unit || '') +
                        '" data-pending="' + item.pending + '"' + disabled + '>' +
                        item.product_name + ' — Ordered: ' + item.qty + ', Pending: ' + item.pending +
                        '</option>';
                });
                $sel.html(html).prop('disabled', false);
                updateDashboardQtyLabel('');
            }

            function showBlockedAlert(blocking) {
                var msg = 'Order <strong>' + blocking.unique_order_id + '</strong> (' + blocking.order_date +
                    ') must be fully dispatched first. ' +
                    '<a href="' + blocking.history_url + '" class="alert-link">Go to pending order</a>';
                $('#dashboardDispatchBlockedAlert').html(msg).removeClass('d-none');
                dashboardDispatchEligible = false;
                resetProductSelect('Dispatch blocked for this order');
                $('#dashboardSaveDispatchBtn').prop('disabled', true);
            }

            function hideBlockedAlert() {
                $('#dashboardDispatchBlockedAlert').addClass('d-none').empty();
                dashboardDispatchEligible = true;
                $('#dashboardSaveDispatchBtn').prop('disabled', false);
            }

            function loadOrderItems(orderId) {
                hideBlockedAlert();
                resetProductSelect(orderId ? 'Loading products…' : '-- Select Order First --');
                if (!orderId) {
                    return;
                }
                var formUrl = $('#dashboardDispatchOrderId option:selected').data('form-url');
                $.get(formUrl)
                    .done(function(data) {
                        if (!data.eligible) {
                            showBlockedAlert(data.blocking_order);
                            return;
                        }
                        populateProductSelect(data.items || []);
                        var savedItem = '{{ old('order_item_id') }}';
                        if (savedItem) {
                            $('#dashboardDispatchOrderItemId').val(savedItem).trigger('change');
                        }
                    })
                    .fail(function() {
                        resetProductSelect('Could not load products');
                    });
            }

            flatpickr('#dashboardDispatchDate', {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd-m-Y',
                allowInput: true,
                defaultDate: '{{ old('dispatch_date') }}' || 'today',
                onChange: function() {
                    $('#dispatch_date-error').text('');
                    $('#dashboardDispatchDate').next('.flatpickr-input').removeClass('is-invalid');
                }
            });

            $('#dashboardDispatchOrderId').on('change', function() {
                loadOrderItems($(this).val());
            });

            $('#dashboardDispatchOrderItemId').on('change', function() {
                var $opt = $(this).find(':selected');
                var pending = parseInt($opt.data('pending')) || 0;
                var unit = $opt.data('product-unit') || '';
                $('#dashboardDispatchProductId').val($opt.data('product-id') || '');
                updateDashboardQtyLabel(unit);
                $('#dashboardDispatchPendingHint').text($opt.val() ? 'Available pending qty: ' + pending + (
                    unit ? ' ' + unit : '') : '');
            });

            $('#dashboardDispatchTransport').on('change', function() {
                loadTrucksForTransporter($(this).val(), $('#dashboardDispatchTruckNumber'),
                    $('#dashboardDispatchDriverContact'), {
                        autoFillContact: true
                    });
            });

            $('#dashboardDispatchModal').on('show.bs.modal', function() {
                $('#dashboardDispatchTruckNumber')
                    .html('<option value="">-- Select Transporter First --</option>')
                    .prop('disabled', true);
                loadDispatchOrders();
            });

            $.validator.addMethod('maxDashboardPending', function(value) {
                if (!dashboardDispatchEligible) return false;
                var $opt = $('#dashboardDispatchOrderItemId').find(':selected');
                if (!$opt.val()) return true;
                return parseInt(value) <= (parseInt($opt.data('pending')) || 0);
            }, 'The entered quantity cannot exceed the pending quantity.');

            $('#dashboardDispatchForm').validate({
                ignore: ':hidden:not(#dashboardDispatchDate)',
                rules: {
                    order_id: {
                        required: true
                    },
                    order_item_id: {
                        required: true
                    },
                    no_of_bags: {
                        required: true,
                        number: true,
                        min: 1,
                        maxDashboardPending: true
                    },
                    dispatch_date: {
                        required: true
                    },
                    transport_id: {
                        required: true
                    },
                    truck_number: {
                        required: true
                    },
                    driver_contact: {
                        required: true
                    },
                    status: {
                        required: true
                    },
                    partial_paid_amount: {
                        dispatchPartialAmount: true
                    },
                },
                messages: {
                    order_id: {
                        required: 'Please select an order.'
                    },
                    order_item_id: {
                        required: 'Please select a product.'
                    },
                    no_of_bags: {
                        required: @json(\App\Support\ProductUnit::requiredMessage())
                    },
                    dispatch_date: {
                        required: 'Please select a dispatch date.'
                    },
                    transport_id: {
                        required: 'Please select a transporter.'
                    },
                    truck_number: {
                        required: 'Please select a truck number.'
                    },
                    driver_contact: {
                        required: 'Driver contact is required.'
                    },
                    status: {
                        required: 'Please select a payment status.'
                    },
                    partial_paid_amount: {
                        dispatchPartialAmount: 'Please enter the paid amount.'
                    },
                },
                errorElement: 'span',
                errorClass: 'text-danger small d-block mt-1',
                errorPlacement: function(error, element) {
                    if (element.attr('name') === 'partial_paid_amount') {
                        error.appendTo('#dashboard_partial_paid_amount-error');
                        return;
                    }
                    var $target = $('#' + element.attr('name') + '-error');
                    if ($target.length) {
                        $target.html(error);
                    } else if (element.attr('name') === 'status') {
                        error.appendTo('#status-error');
                    } else if (element.attr('id') === 'dashboardDispatchDate') {
                        error.appendTo('#dispatch_date-error');
                    } else {
                        error.insertAfter(element);
                    }
                },
                highlight: function(el) {
                    var $e = $(el);
                    $e.attr('id') === 'dashboardDispatchDate' ? $e.next('.flatpickr-input').addClass(
                        'is-invalid') : $e.addClass('is-invalid');
                },
                unhighlight: function(el) {
                    var $e = $(el);
                    $e.attr('id') === 'dashboardDispatchDate' ? $e.next('.flatpickr-input').removeClass(
                        'is-invalid') : $e.removeClass('is-invalid');
                },
                submitHandler: function(form) {
                    if (!dashboardDispatchEligible) {
                        return false;
                    }
                    form.submit();
                },
            });

            @if (session('open_dashboard_dispatch_modal') || ($errors->any() && old('from_dashboard')))
                (function() {
                    var savedTransporter = '{{ old('transport_id') }}';
                    var savedTruck = '{{ old('truck_number') }}';
                    var savedContact = '{{ old('driver_contact') }}';
                    var savedOrder = '{{ old('order_id') }}';

                    loadDispatchOrders(function() {
                        if (savedOrder) {
                            $('#dashboardDispatchOrderId').val(savedOrder);
                            loadOrderItems(savedOrder);
                        }

                        if (savedTransporter) {
                            $('#dashboardDispatchTransport').val(savedTransporter);
                            loadTrucksForTransporter(savedTransporter, $('#dashboardDispatchTruckNumber'),
                                $('#dashboardDispatchDriverContact'), {
                                    setTruckNumber: savedTruck,
                                    setDriverContact: savedContact || null
                                });
                        }

                        (new bootstrap.Modal(document.getElementById('dashboardDispatchModal'))).show();
                    });
                })();
            @endif
        });
    </script>
@endcan
@endsection
