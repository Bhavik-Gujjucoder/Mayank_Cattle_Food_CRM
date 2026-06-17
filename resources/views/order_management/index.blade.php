@extends('layouts.main')
@section('content')
@section('title')
    {{ $page_title }}
@endsection

@section('styles')
<style>
    /* ── Order list module — responsive layout ── */
    .order-list-module .cls-form-right {
        flex-shrink: 0;
    }
    .order-list-module .comm-header-right-btn {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: flex-end;
    }
    .order-list-module .order-table-scroll {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-gutter: stable;
    }
    .order-list-module .order-table-scroll-hint {
        display: none;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.78rem;
        color: #667085;
        margin-bottom: 0.65rem;
    }
    .order-list-module .dataTables_wrapper {
        width: 100%;
        min-width: 0;
    }
    .order-list-module .dataTables_length,
    .order-list-module .dataTables_info,
    .order-list-module .dataTables_paginate {
        flex-wrap: wrap;
    }
    #order_table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100% !important;
        min-width: 1080px;
    }
    #order_table tbody tr.order-group-row > td {
        background: #fff;
        border-top: 2px solid #e4e7ec;
        vertical-align: middle;
        padding-top: 10px;
        padding-bottom: 10px;
    }
    #order_table tbody tr.order-group-row:first-child > td {
        border-top: none;
    }
    #order_table tbody tr.order-group-row:not(.order-row-expanded) > td {
        border-bottom: 2px solid #e4e7ec;
    }
    #order_table tbody tr.order-row-expanded > td {
        background: #f8fafc;
        border-top: 2px solid #3554d1;
        border-bottom: none;
        padding-top: 12px;
        padding-bottom: 12px;
    }
    #order_table tbody tr.order-detail-row > td {
        padding: 0 !important;
        background: #f2f4f7;
        border-top: none;
        border-bottom: 3px solid #d0d5dd;
    }
    #order_table tbody tr.order-detail-row + tr.order-group-row > td {
        border-top: 10px solid #eef1f5;
    }
    #order_table tbody tr.order-detail-row > td .ol-detail-panel {
        margin: 0 14px 14px;
        padding: 14px 16px;
        background: #fff;
        border: 1px solid #e4e7ec;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    }
    .order-expand-btn {
        width: 32px;
        height: 32px;
        padding: 0;
        line-height: 1;
        color: #3554d1;
    }
    .order-expand-btn:hover {
        background: #eef2ff;
        color: #1e3a8a;
    }
    .ol-amount-cell .ol-amount-avg {
        font-size: 0.78rem;
        margin-top: 2px;
    }
    .ol-dispatch-cell {
        min-width: 140px;
    }
    .ol-dispatch-bar {
        height: 6px;
        border-radius: 4px;
        background: #e9ecef;
        margin-bottom: 4px;
    }
    .ol-dispatch-meta {
        line-height: 1.3;
        white-space: nowrap;
    }
    #order_table tbody tr.child > td,
    #order_table tbody tr.order-detail-row > td {
        width: 100% !important;
    }
    @media (max-width: 991.98px) {
        .order-list-module .cls-cardhed-part {
            flex-direction: column;
            align-items: stretch;
        }
        .order-list-module .cls-form-left,
        .order-list-module .cls-form-right {
            width: 100%;
        }
        .order-list-module .cls-form-left {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 0.75rem;
        }
        .order-list-module .common-hed-form.cls-form-select-input,
        .order-list-module .common-hed-form.cls-form-serc {
            flex: 1 1 180px;
            min-width: 0;
        }
        .order-list-module .common-hed-form.cls-form-select-input .select2-container {
            width: 100% !important;
        }
        .order-list-module .comm-header-right-btn {
            justify-content: flex-start;
            width: 100%;
        }
        .order-list-module .order-table-scroll-hint {
            display: flex;
        }
        #order_table {
            min-width: 960px;
        }
        .order-list-module #order_table tbody tr.order-detail-row > td .ol-detail-panel {
            margin: 0 10px 12px;
            padding: 12px;
        }
    }
    @media (max-width: 767.98px) {
        .order-list-module .card-header {
            padding: 0.75rem;
        }
        .order-list-module .cls-form-left {
            flex-direction: column;
            gap: 0.5rem;
        }
        .order-list-module .common-hed-form.cls-form-select-input,
        .order-list-module .common-hed-form.cls-form-serc {
            flex: 1 1 100%;
            width: 100%;
        }
        #order_table {
            min-width: 720px;
        }
        .ol-dispatch-meta {
            white-space: normal;
        }
        .ol-amount-cell .ol-amount-total {
            font-size: 0.88rem;
        }
        .order-list-module #order_table tbody tr.order-detail-row > td .ol-detail-panel {
            margin: 0 6px 10px;
            padding: 10px;
            border-radius: 6px;
        }
        .ol-detail-head {
            flex-direction: column;
            align-items: flex-start;
        }
        .ol-detail-meta {
            line-height: 1.4;
        }
    }
    @media (max-width: 575.98px) {
        .order-list-module .comm-header-right-btn .btn {
            width: 100%;
            justify-content: center;
        }
        #order_table {
            min-width: 560px;
        }
        #order_table thead th,
        #order_table tbody td {
            font-size: 0.8125rem;
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
        .order-expand-btn {
            width: 28px;
            height: 28px;
        }
    }
    .ol-detail-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 10px;
    }
    .ol-detail-title {
        font-weight: 600;
        font-size: 0.88rem;
        color: #344054;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }
    .ol-detail-order-tag {
        display: inline-block;
        font-size: 0.72rem;
        font-weight: 600;
        color: #3554d1;
        background: #eef2ff;
        border: 1px solid #c7d2fe;
        border-radius: 4px;
        padding: 2px 8px;
        letter-spacing: 0.02em;
    }
    .ol-detail-meta {
        font-size: 0.8rem;
        color: #667085;
    }
    .ol-detail-table-wrap {
        -webkit-overflow-scrolling: touch;
    }
    .ol-detail-table {
        min-width: 640px;
    }
    .ol-detail-table th {
        font-size: 0.78rem;
        white-space: nowrap;
    }
    .ol-detail-table td {
        font-size: 0.82rem;
        vertical-align: middle;
    }
    .ol-detail-prog {
        height: 6px;
        border-radius: 4px;
    }
    .ol-badge-dispatched {
        display: inline-block;
        min-width: 28px;
        padding: 2px 8px;
        border-radius: 4px;
        background: #e8f4fd;
        color: #0d6efd;
        font-weight: 600;
        font-size: 0.8rem;
    }
    .ol-badge-pending {
        display: inline-block;
        min-width: 28px;
        padding: 2px 8px;
        border-radius: 4px;
        background: #fff3cd;
        color: #b45309;
        font-weight: 600;
        font-size: 0.8rem;
    }
    .ol-badge-done {
        display: inline-block;
        min-width: 28px;
        padding: 2px 8px;
        border-radius: 4px;
        background: #d1fae5;
        color: #047857;
        font-weight: 600;
        font-size: 0.8rem;
    }
</style>
@endsection

<div class="card order-list-module">
    {{-- <div class="card-header"> --}}
    <div class="card-header">
        <div class="cls-cardhed-part">
            <div class="cls-form-left">
                <div class="common-hed-form cls-form-serc">
                    <div class="icon-form">
                        <span class="form-icon"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" id="customSearch" placeholder="Search Orders">
                    </div>
                </div>
                <div class="common-hed-form cls-form-select-input">
                    <label class="col-form-label">From Date</label>
                    <div class="icon-form">
                        <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                        <input type="text" id="orderDateFrom" class="form-control flatpickr" placeholder="DD-MM-YYYY"
                            autocomplete="off">
                    </div>
                </div>
                <div class="common-hed-form cls-form-select-input">
                    <label class="col-form-label">To Date</label>
                    <div class="icon-form">
                        <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                        <input type="text" id="orderDateTo" class="form-control flatpickr" placeholder="DD-MM-YYYY"
                            autocomplete="off">
                    </div>
                </div>
                @if (\App\Support\SalesScope::showBrandFilter())
                    <div class="common-hed-form cls-form-select-input">
                        <label class="col-form-label">Brand </label>
                        <select class="form-select select search-dropdown" name="brand_id" id="BrandId">
                            <option value="all">All Brand</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @if (\App\Support\SalesScope::showBrokerFilter())
                    <div class="common-hed-form cls-form-select-input">
                        <label class="col-form-label">Broker Person</label>
                        <select class="form-select select search-dropdown" name="broker_id" id="broker_id">
                            <option value="all">All Brokers</option>
                            @foreach ($brokers as $broker)
                                <option value="{{ $broker->id }}">{{ $broker->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @if (\App\Support\SalesScope::showDealerFilter())
                    <div class="common-hed-form cls-form-select-input">
                        <label class="col-form-label">Dealer</label>
                        <select class="form-select select search-dropdown" name="dealer_id" id="dealerFilter">
                            <option value="all">All Dealers</option>
                            @foreach ($dealers as $dealer)
                                <option value="{{ $dealer->id }}">
                                    {{ $dealer->user?->name ?? $dealer->firm_shop_name ?? '—' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="common-hed-form cls-form-select-input d-flex align-items-end">
                    <button type="button" class="btn btn-light" id="resetOrderFilters">
                        <i class="ti ti-refresh me-1"></i>Reset
                    </button>
                </div>

            </div>
            <div class="cls-form-right">
                <div class="comm-header-right-btn">
                    @can('add-order')
                        <a href="{{ route('order.create') }}" class="btn btn-primary">
                            <i class="ti ti-square-rounded-plus me-2"></i>Add Soda/Order
                        </a>
                    @endcan
                </div>

            </div>
        </div>
        <!-- Search -->
        {{-- <div class="row align-items-center">
                <div class="col-sm-4">
                    <div class="icon-form mb-3 mb-sm-0">
                        <span class="form-icon"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" id="customSearch" placeholder="Search Orders">
                    </div>
                </div>
                @if (\App\Support\SalesScope::showBrandFilter())
                    <div class="col-sm-4 col-lg-2 col-md-12">
                        <div class="mb-3">
                            <label class="col-form-label">Brand </label>
                            <select class="form-select select search-dropdown" name="brand_id" id="BrandId">
                                <option value="all">All Brand</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
                @if (\App\Support\SalesScope::showBrokerFilter())
                    <div class="col-sm-4 col-lg-2 col-md-12">
                        <div class="mb-3">
                            <label class="col-form-label">Broker Person</label>
                            <select class="form-select select search-dropdown" name="broker_id" id="broker_id">
                                <option value="all">All Brokers</option>
                                @foreach ($brokers as $broker)
                                    <option value="{{ $broker->id }}">{{ $broker->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
                <div class="col-sm-4">
                    <div class="d-flex align-items-center flex-wrap row-gap-2 justify-content-sm-end">

                        @can('add-order')
                            <a href="{{ route('order.create') }}" class="btn btn-primary">
                                <i class="ti ti-square-rounded-plus me-2"></i>Add Soda/Order
                            </a>
                        @endcan
                    </div>
                </div>
            </div> --}}
        <!-- /Search -->
    </div>
    <div class="card-body">
        <p class="order-table-scroll-hint mb-0">
            <i class="ti ti-arrows-horizontal"></i>
            Swipe horizontally to see all columns
        </p>
        <div class="table-responsive custom-table order-table-scroll">
            <table class="table dataTable no-footer" id="order_table">
                <button class="btn btn-danger me-2" id="bulk_delete_button" style="display:none;">
                    <i class="ti ti-trash me-2"></i>Delete Selected
                </button>
                <thead class="thead-light">
                    <tr>
                        <th hidden>ID</th>
                        {{-- <th class="no-sort" scope="col">
                            <label class="checkboxs">
                                <input type="checkbox" id="select-all" class="order_checkbox">
                                <span class="checkmarks"></span>
                            </label>
                        </th> --}}
                        <th class="no-sort" style="width:40px;" scope="col"></th>
                        <th class="no-sort" scope="col">Sr No</th>
                        <th scope="col">Order ID</th>
                        <th scope="col">Broker</th>
                        <th scope="col">Brand</th>
                        <th scope="col">Dealer</th>
                        <th scope="col">Order Date</th>
                        <th scope="col" class="no-sort">Amount</th>
                        <th scope="col" class="no-sort">Dispatch</th>
                        <th scope="col" class="no-sort">Due Charges</th>
                        <th scope="col">Payment Status</th>
                        {{-- <th scope="col">Order Status</th> --}}
                        <th scope="col" @unless(auth()->user()->canAny(['edit-order', 'delete-order', 'view-dispatch'])) hidden @endunless>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
    {{-- </div> --}}

@endsection
@section('script')
    <script>
        $(document).ready(function() {
            $('.search-dropdown').select2({
                placeholder: 'Select'
            });
        });

        const isShowAction = {{ auth()->user()->canAny(['edit-order', 'delete-order', 'view-dispatch'])? 'true': 'false' }};
        const isShowCheckbox = {{ auth()->user()->can('delete-order') ? 'true' : 'false' }};

        const expandedOrderIds = new Set();
        const orderDetailHtmlCache = new Map();
        const orderListItemsDetailUrl = @json(route('order.listItemsDetail', ['order' => '__ORDER__']));

        function applyExpandedRowUi(row) {
            var tr = $(row.node());
            tr.addClass('order-row-expanded');
            tr.next('tr.child').addClass('order-detail-row');
            tr.find('.order-expand-btn')
                .attr('title', 'Hide product details')
                .attr('aria-expanded', 'true')
                .find('i')
                .removeClass('ti-chevron-down')
                .addClass('ti-chevron-up');
        }

        function showOrderDetails(row) {
            var data = row.data();
            var id   = data.id;
            expandedOrderIds.add(id);

            if (orderDetailHtmlCache.has(id)) {
                row.child(orderDetailHtmlCache.get(id)).show();
                applyExpandedRowUi(row);
                return;
            }

            var url = orderListItemsDetailUrl.replace('__ORDER__', id);
            $.getJSON(url)
                .done(function(res) {
                    if (!res.html) {
                        return;
                    }
                    orderDetailHtmlCache.set(id, res.html);
                    row.child(res.html).show();
                    applyExpandedRowUi(row);
                })
                .fail(function() {
                    expandedOrderIds.delete(id);
                    if (typeof show_error === 'function') {
                        show_error('Could not load order details. Please try again.');
                    }
                });
        }

        function hideOrderDetails(row) {
            var tr = $(row.node());
            var id = row.data().id;
            expandedOrderIds.delete(id);
            if (!row.child.isShown()) {
                return;
            }
            row.child.hide();
            tr.removeClass('order-row-expanded');
            tr.next('tr.child').removeClass('order-detail-row');
            tr.find('.order-expand-btn')
                .attr('title', 'Show product details')
                .attr('aria-expanded', 'false')
                .find('i')
                .removeClass('ti-chevron-up')
                .addClass('ti-chevron-down');
        }

        function restoreExpandedOrderRows() {
            order_table.rows({ page: 'current' }).every(function() {
                var id = this.data().id;
                if (expandedOrderIds.has(id) && orderDetailHtmlCache.has(id)) {
                    if (!this.child.isShown()) {
                        this.child(orderDetailHtmlCache.get(id)).show();
                        applyExpandedRowUi(this);
                    }
                }
            });
        }

        /* Column indexes — must match DataTables columns[] order */
        var ORDER_COL = {
            broker: 4,
            brand: 5
        };

        function adjustOrderTableLayout() {
            if (typeof order_table === 'undefined') {
                return;
            }

            var w = window.innerWidth;
            var showBroker = w >= 768;
            var showBrand  = w >= 576;
            var brokerCol  = order_table.column(ORDER_COL.broker);
            var brandCol   = order_table.column(ORDER_COL.brand);

            if (brokerCol.visible() !== showBroker) {
                brokerCol.visible(showBroker);
            }
            if (brandCol.visible() !== showBrand) {
                brandCol.visible(showBrand);
            }

            order_table.columns.adjust();

            order_table.rows({ page: 'current' }).every(function() {
                if ($(this.node()).hasClass('order-row-expanded')) {
                    $(this.node()).next('tr.child').addClass('order-detail-row');
                }
            });
        }

        var order_table = $('#order_table').DataTable({
            pageLength: 10,
            deferRender: true,
            processing: true,
            serverSide: true,
            responsive: false,
            autoWidth: false,
            dom: 'lrtip',
            order: [
                [0, 'desc']
            ],
            ajax: {
                url: "{{ route('order.index') }}",
                data: function(d) {
                    if ($('#broker_id').length) {
                        d.broker_id = $('#broker_id').val();
                    }
                    if ($('#BrandId').length) {
                        d.brand_id = $('#BrandId').val();
                    }
                    if ($('#dealerFilter').length) {
                        d.dealer_id = $('#dealerFilter').val() || 'all';
                    }
                    d.date_from = $('#orderDateFrom').val() || '';
                    d.date_to   = $('#orderDateTo').val() || '';
                }
            },
            columns: [{
                    data: 'id',
                    name: 'id',
                    visible: false,
                    searchable: false
                },
                // {
                //     data: 'checkbox',
                //     name: 'checkbox',
                //     orderable: false,
                //     searchable: false,
                //     visible: isShowCheckbox
                // },
                {
                    data: 'expand_control',
                    name: 'expand_control',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                },
                {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'unique_order_id',
                    name: 'unique_order_id',
                    searchable: true
                },
                {
                    data: 'broker_name',
                    name: 'broker_name',
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'brand_name',
                    name: 'brand_name',
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'dealer_name',
                    name: 'dealer_name',
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'order_date',
                    name: 'order_date',
                    searchable: false
                },
                {
                    data: 'amount_summary',
                    name: 'amount_summary',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'dispatch_summary',
                    name: 'dispatch_summary',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'receivable_summary',
                    name: 'receivable_summary',
                    orderable: false,
                    searchable: false,
                    className: 'text-end'
                },
                {
                    data: 'payment_status',
                    name: 'payment_status',
                    orderable: false,
                    searchable: true
                },
                // { data: 'order_status',   name: 'order_status',   orderable: false, searchable: false },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    visible: isShowAction
                },
            ],
            createdRow: function(row) {
                $(row).addClass('order-group-row');
            },
            initComplete: function() {
                adjustOrderTableLayout();
            },
            drawCallback: function() {
                restoreExpandedOrderRows();
                order_table.columns.adjust();
            }
        });

        var orderTableResizeTimer;
        $(window).on('resize', function() {
            clearTimeout(orderTableResizeTimer);
            orderTableResizeTimer = setTimeout(adjustOrderTableLayout, 150);
        });

        /* Toggle product detail panel */
        $(document).on('click', '.order-expand-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var tr  = $(this).closest('tr');
            var row = order_table.row(tr);

            if (!row.length) {
                return;
            }

            var id = row.data().id;

            if (tr.hasClass('order-row-expanded')) {
                hideOrderDetails(row);
            } else {
                showOrderDetails(row);
            }
        });

        var orderDateFromPicker = flatpickr('#orderDateFrom', {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd-m-Y',
            allowInput: true,
            onChange: function() {
                order_table.draw();
            },
        });

        var orderDateToPicker = flatpickr('#orderDateTo', {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd-m-Y',
            allowInput: true,
            onChange: function() {
                order_table.draw();
            },
        });

        /* Broker / Brand / Dealer filters */
        $('#broker_id, #BrandId, #dealerFilter').on('change', function() {
            order_table.draw();
        });

        /* Custom search */
        bindDebouncedDataTableSearch('#customSearch', order_table);

        /* Reset all list filters */
        $('#resetOrderFilters').on('click', function() {
            $('#customSearch').val('');
            order_table.search('');
            orderDateFromPicker.clear();
            orderDateToPicker.clear();

            if ($('#BrandId').length) {
                $('#BrandId').val('all');
            }
            if ($('#broker_id').length) {
                $('#broker_id').val('all');
            }
            if ($('#dealerFilter').length) {
                $('#dealerFilter').val('all');
            }

            $('.search-dropdown').trigger('change.select2');
            collapsedOrderIds.clear();
            order_table.draw();
        });

        /* ── Sequential dispatch check ──────────────────────────────── */
        $(document).on('click', '.dispatch-check-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var checkUrl = $btn.data('check-url');
            var historyUrl = $btn.data('history-url');

            /* Temporary loading state so the user knows something is happening */
            var origHtml = $btn.html();
            $btn.addClass('disabled').html('<i class="ti ti-loader-2 me-1"></i> Checking…');

            $.get(checkUrl)
                .done(function(data) {
                    $btn.removeClass('disabled').html(origHtml);

                    if (data.eligible) {
                        window.location.href = historyUrl;
                        return;
                    }

                    /* ── Build the pending-items table for the popup ── */
                    var bo = data.blocking_order;
                    var rows = '';
                    $.each(bo.pending_items, function(i, item) {
                        rows +=
                            '<tr>' +
                            '<td>' + item.product_name + '</td>' +
                            '<td><span class="dbp-td-ordered">' + item.ordered_qty + '</span></td>' +
                            '<td><span class="dbp-badge-dispatched">' + item.dispatched_qty +
                            '</span></td>' +
                            '<td><span class="dbp-badge-pending">' + item.pending_qty + '</span></td>' +
                            '</tr>';
                    });

                    var itemCount = bo.pending_items.length;

                    var html =
                        /* ── gradient header ── */
                        '<div class="dbp-header">' +
                        '<div class="dbp-header-icon-wrap">' +
                        '<i class="ti ti-truck-off"></i>' +
                        '</div>' +
                        '<div class="dbp-header-title">Dispatch Blocked</div>' +
                        '<div class="dbp-header-sub">This dealer has older pending orders. Please complete dispatch for those orders first, then dispatch the latest order.</div>' +
                        '</div>' +

                        /* ── body ── */
                        '<div class="dbp-body">' +

                        /* blocking order info bar */
                        '<div class="dbp-order-info-bar">' +
                        '<span class="dbp-order-info-label">Pending Order</span>' +
                        '<span class="dbp-order-id-chip"><i class="ti ti-receipt"></i> ' + bo.unique_order_id +
                        '</span>' +
                        '<span class="dbp-order-date"><i class="ti ti-calendar-event"></i> ' + bo.order_date +
                        '</span>' +
                        '</div>' +

                        /* section label */
                        '<div class="dbp-section-head">' +
                        '<span class="dbp-section-title">Pending Items</span>' +
                        '<span class="dbp-section-count">' + itemCount + ' item' + (itemCount !== 1 ? 's' :
                            '') + '</span>' +
                        '</div>' +

                        /* table */
                        '<div class="dbp-table-wrap">' +
                        '<table class="dbp-table">' +
                        '<thead><tr>' +
                        '<th>Product</th>' +
                        '<th>Ordered</th>' +
                        '<th>Dispatched</th>' +
                        '<th>Pending</th>' +
                        '</tr></thead>' +
                        '<tbody>' + rows + '</tbody>' +
                        '</table>' +
                        '</div>' +

                        '</div>';

                    Swal.fire({
                        html: html,
                        width: 540,
                        padding: '0',
                        showCancelButton: true,
                        confirmButtonText: '<i class="ti ti-truck me-1"></i> Go to Pending Order',
                        cancelButtonText: '<i class="ti ti-x me-1"></i> Close',
                        customClass: {
                            popup: 'dbp-popup',
                            htmlContainer: 'dbp-html-container',
                            confirmButton: 'btn dbp-btn-primary',
                            cancelButton: 'btn dbp-btn-secondary',
                            actions: 'dbp-actions',
                        },
                        buttonsStyling: false,
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            window.location.href = bo.history_url;
                        }
                    });
                })
                .fail(function() {
                    /* On network failure, degrade gracefully — navigate directly */
                    $btn.removeClass('disabled').html(origHtml);
                    window.location.href = historyUrl;
                });
        });

        /* ── Single delete ──────────────────────────────────────────── */
        $(document).on('click', '.deleteOrder', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var orderId = $btn.data('id');
            var checkUrl = $btn.data('check-url');

            /* 1. AJAX pre-check: does this order have dispatched items? */
            $.get(checkUrl)
                .done(function(data) {
                    if (!data.can_delete) {
                        /* Blocked — show dispatch details popup */
                        showDispatchBlockedPopup(data.dispatched_items);
                        return;
                    }
                    /* Safe to delete — show standard confirm */
                    confirmDeletion(function() {
                        $('#order-delete-form-' + orderId).submit();
                    });
                })
                .fail(function() {
                    /* Network error — fall back to standard confirm */
                    confirmDeletion(function() {
                        $('#order-delete-form-' + orderId).submit();
                    });
                });
        });

        /* ── Bulk delete — select all ────────────────────────────────── */
        $('#select-all').on('change', function() {
            $('.order_checkbox').prop('checked', this.checked);
            toggleBulkBtn();
        });

        $(document).on('change', '.order_checkbox', function() {
            toggleBulkBtn();
        });

        function toggleBulkBtn() {
            let count = $('.order_checkbox:checked').not('#select-all').length;
            count > 0 ? $('#bulk_delete_button').show() : $('#bulk_delete_button').hide();
        }

        $('#bulk_delete_button').on('click', function() {
            confirmDeletion(function() {
                var selectedIds = $('.order_checkbox:checked').not('#select-all').map(function() {
                    return $(this).data('id');
                }).get();

                if (selectedIds.length > 0) {
                    $.ajax({
                        url: "{{ route('order.bulkDelete') }}",
                        method: 'POST',
                        data: {
                            ids: selectedIds,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            show_success(response.message);
                            order_table.ajax.reload();
                            $('#bulk_delete_button').hide();
                            $('#select-all').prop('checked', false);
                        },
                        error: function(xhr) {
                            var res = xhr.responseJSON;
                            if (res && res.blocked) {
                                /* One or more orders have dispatch history */
                                Swal.fire({
                                    html: buildBulkBlockedHtml(res.blocked_orders),
                                    showConfirmButton: true,
                                    confirmButtonText: '<i class="ti ti-check me-1"></i> Got it',
                                    width: 480,
                                    padding: '0',
                                    customClass: {
                                        popup: 'my-custom-popup od-popup',
                                        htmlContainer: 'od-html-container',
                                        confirmButton: 'btn od-confirm-btn px-4',
                                        actions: 'od-actions',
                                    },
                                    buttonsStyling: false,
                                });
                            } else {
                                show_error('An error occurred while deleting.');
                            }
                        }
                    });
                }
            });
        });

        /* ── Builds the HTML for the "order blocked" popup (single delete) ── */
        function showDispatchBlockedPopup(items) {
            /* Build one table row per dispatched item */
            var rows = '';
            $.each(items, function(i, item) {
                var isFullyDone = item.remaining_qty <= 0;
                var remainBadge = isFullyDone ?
                    '<span class="od-badge-done"><i class="ti ti-check me-1"></i>Done</span>' :
                    '<span class="od-badge-pending">' + item.remaining_qty + '</span>';
                var dispatchBadge = '<span class="od-badge-dispatched">' + item.dispatched_qty + '</span>';

                rows +=
                    '<tr>' +
                    '<td class="od-td-product"><i class="ti ti-box me-1"></i>' + item.product_name + '</td>' +
                    '<td class="od-td-ordered">' + item.ordered_qty + '</td>' +
                    '<td>' + dispatchBadge + '</td>' +
                    '<td>' + remainBadge + '</td>' +
                    '<td class="od-td-date"><i class="ti ti-calendar-event me-1"></i>' + item.last_dispatch +
                    '</td>' +
                    '</tr>';
            });

            var html =
                /* Alert bar */
                '<div class="od-alert-bar">' +
                '<span class="od-alert-icon"><i class="ti ti-trash-off"></i></span>' +
                '<div>' +
                '<div class="od-alert-title">Cannot Delete This Order</div>' +
                '<div class="od-alert-sub">This order has dispatched product items and is protected from deletion.</div>' +
                '</div>' +
                '</div>' +
                /* Warning message */
                '<div class="od-warn-text">' +
                '<i class="ti ti-alert-triangle me-1"></i>' +
                'The following product item(s) have already been dispatched. ' +
                'Please review the dispatch history before attempting to delete.' +
                '</div>' +
                /* Section label */
                '<div class="od-section-head">' +
                '<span class="od-section-title"><i class="ti ti-truck me-1"></i>Dispatch History</span>' +
                '<span class="od-section-count">' + items.length + ' item' + (items.length > 1 ? 's' : '') + '</span>' +
                '</div>' +
                /* Dispatch details table */
                '<div class="od-table-wrap">' +
                '<table class="od-table">' +
                '<thead><tr>' +
                '<th>Product</th>' +
                '<th>Ordered</th>' +
                '<th>Dispatched</th>' +
                '<th>Remaining</th>' +
                '<th>Last Dispatch</th>' +
                '</tr></thead>' +
                '<tbody>' + rows + '</tbody>' +
                '</table>' +
                '</div>' +
                /* Footer */
                '<div class="od-footer-note">' +
                '<i class="ti ti-info-circle me-1"></i>' +
                'To delete this order, all dispatches for its items must be cancelled first.' +
                '</div>';

            Swal.fire({
                html: html,
                showConfirmButton: true,
                confirmButtonText: '<i class="ti ti-check me-1"></i> Got it',
                width: 600,
                padding: '0',
                customClass: {
                    popup: 'my-custom-popup od-popup',
                    htmlContainer: 'od-html-container',
                    confirmButton: 'btn od-confirm-btn px-4',
                    actions: 'od-actions',
                },
                buttonsStyling: false,
            });
        }

        /* ── Builds minimal HTML for bulk-blocked notification ── */
        function buildBulkBlockedHtml(blockedOrders) {
            var orderList = $.map(blockedOrders, function(id) {
                return '<span class="od-order-chip">' + id + '</span>';
            }).join(' ');

            return '<div class="od-alert-bar">' +
                '<span class="od-alert-icon"><i class="ti ti-trash-off"></i></span>' +
                '<div>' +
                '<div class="od-alert-title">Cannot Delete Selected Orders</div>' +
                '<div class="od-alert-sub">One or more selected orders have dispatched items.</div>' +
                '</div>' +
                '</div>' +
                '<div style="padding:16px 22px 4px;">' +
                '<p class="mb-2" style="font-size:0.88rem;color:#555;">The following order(s) have dispatch history and cannot be deleted:</p>' +
                '<div style="margin-bottom:4px;">' + orderList + '</div>' +
                '</div>' +
                '<p class="od-footer-note">' +
                '<i class="ti ti-info-circle me-1"></i>' +
                'Remove the dispatched orders from your selection and try again.' +
                '</p>';
        }

        /* ── Standard delete confirmation (used when delete is safe) ─── */
        function confirmDeletion(callback) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'You want to remove this Order? Once deleted, it cannot be recovered.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                customClass: {
                    popup: 'my-custom-popup',
                    title: 'my-custom-title',
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-secondary',
                    icon: 'my-custom-icon swal2-warning'
                }
            }).then((result) => {
                if (result.isConfirmed) callback();
            });
        }
    </script>
@endsection
