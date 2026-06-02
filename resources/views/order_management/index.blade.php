@extends('layouts.main')
@section('content')
@section('title')
    {{ $page_title }}
@endsection

<div class="card">
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
                    <label class="col-form-label">Brand </label>
                    <select class="form-select select search-dropdown" name="brand_id" id="BrandId">
                        <option value="all">All Brand</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
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
        <div class="table-responsive custom-table">
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
                        <th class="no-sort" scope="col">Sr No</th>
                        <th scope="col">Order ID</th>
                        <th scope="col">Broker</th>
                        <th scope="col">Brand</th>
                        <th scope="col">Dealer</th>
                        <th scope="col">Order Date</th>
                        {{-- <th scope="col">Grand Total</th> --}}
                        <th scope="col">Payment Status</th>
                        {{-- <th scope="col">Order Status</th> --}}
                        @canany(['edit-order', 'delete-order', 'add-dispatch'])
                            <th scope="col">Action</th>
                        @endcanany
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

        const isShowAction = {{ auth()->user()->canAny(['edit-order', 'delete-order', 'add-dispatch'])? 'true': 'false' }};
        const isShowCheckbox = {{ auth()->user()->can('delete-order') ? 'true' : 'false' }};

        var order_table = $('#order_table').DataTable({
            pageLength: 10,
            deferRender: true,
            processing: true,
            serverSide: true,
            responsive: true,
            dom: 'lrtip',
            order: [
                [0, 'desc']
            ],
            ajax: {
                url: "{{ route('order.index') }}",
                data: function(d) {
                    d.broker_id = $('#broker_id').val();
                    d.brand_id = $('#BrandId').val();
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
                // {
                //     data: 'grand_total',
                //     name: 'grand_total',
                //     searchable: false
                // },
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
            ]
        });

        /* Broker /Brand */
        $('#broker_id, #BrandId').on('change', function() {
            order_table.draw();
        });

        /* Custom search */
        $('#customSearch').on('keyup', function() {
            order_table.search(this.value).draw();
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
