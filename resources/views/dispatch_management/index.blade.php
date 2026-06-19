@extends('layouts.main')
@section('title')
    {{-- {{ $page_title }} --}}
@endsection
@section('content')

<div class="card">

    <div class="card-header">
        <div class="row align-items-center g-3 mb-0">
            <div class="col-12 col-sm-auto me-auto">
                <div class="d-flex align-items-center gap-2">
                    <div class="dispatch-index-icon">
                        <i class="ti ti-truck"></i>
                    </div>
                    <div>
                        <div class="dispatch-index-eyebrow">Records</div>
                        <div class="dispatch-index-title">Dispatch Management</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="cls-cardhed-part mt-3 pt-3 border-top">
            <div class="cls-form-left">
                <div class="common-hed-form cls-form-select-input">
                    <label class="col-form-label">From Date</label>
                    <div class="icon-form">
                        <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                        <input type="text" id="dateFrom" class="form-control flatpickr" placeholder="DD-MM-YYYY"
                            autocomplete="off">
                    </div>
                </div>
                <div class="common-hed-form cls-form-select-input">
                    <label class="col-form-label">To Date</label>
                    <div class="icon-form">
                        <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                        <input type="text" id="dateTo" class="form-control flatpickr" placeholder="DD-MM-YYYY"
                            autocomplete="off">
                    </div>
                </div>
                @if (\App\Support\SalesScope::showDealerFilter())
                    <div class="common-hed-form cls-form-select-input">
                        <label class="col-form-label">Dealer</label>
                        <select class="form-select select search-dropdown" id="dealerFilter" name="dealer_id">
                            <option value="all">All Dealers</option>
                            @foreach ($dealers as $dealer)
                                <option value="{{ $dealer->id }}">
                                    {{ $dealer->user?->name ?? $dealer->firm_shop_name ?? '—' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="common-hed-form cls-form-select-input">
                    <label class="col-form-label">Order</label>
                    <select class="form-select select search-dropdown" id="orderFilter" name="order_id">
                        <option value="all">All Orders</option>
                        @foreach ($orders as $order)
                            <option value="{{ $order->id }}" data-dealer-id="{{ $order->dealer_id }}">
                                {{ $order->unique_order_id }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="common-hed-form cls-form-select-input">
                    <label class="col-form-label">Product</label>
                    <select class="form-select select search-dropdown" id="productFilter" name="product_id">
                        <option value="all">All Products</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="common-hed-form cls-form-select-input d-flex align-items-end">
                    <button type="button" class="btn btn-danger" id="resetDispatchFilters">
                        <i class="ti ti-refresh me-1"></i>Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive custom-table">
            <table class="table dataTable no-footer" id="dispatch_table">
                <thead class="thead-light">
                    <tr>
                        <th hidden>ID</th>
                        <th class="no-sort" style="width:60px;">Sr No</th>
                        <th>Order ID</th>
                        <th>Product</th>
                        <th style="width:130px;">Bag / Ton / KG</th>
                        <th>Dealer Name</th>
                        <th style="width:130px;">Dispatch Date</th>
                        <th>Transport</th>
                        <th>Truck Number</th>
                        <th>Driver Contact</th>
                        <th class="text-end" style="width:110px;">Late Fee</th>
                        <th class="text-end" style="width:120px;">Balance Due</th>
                        <th style="width:100px;">Status</th>
                        <th class="text-center no-sort" style="width:80px;">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

</div>

@endsection
@section('script')
<script>
$(document).ready(function () {

    $('.search-dropdown').select2({ width: '100%' });

    const filterParams = {
        date_from:  '#dateFrom',
        date_to:    '#dateTo',
        dealer_id:  '#dealerFilter',
        order_id:   '#orderFilter',
        product_id: '#productFilter',
    };

    const urlParams = new URLSearchParams(window.location.search);
    Object.entries(filterParams).forEach(function ([key, selector]) {
        if ($(selector).length && urlParams.has(key)) {
            $(selector).val(urlParams.get(key));
        }
    });

    function syncFilterUrl() {
        const params = new URLSearchParams();
        Object.entries(filterParams).forEach(function ([key, selector]) {
            if (!$(selector).length) return;
            const value = $(selector).val();
            if (value && value !== 'all') {
                params.set(key, value);
            }
        });
        const qs = params.toString();
        window.history.replaceState({}, '', qs ? ('?' + qs) : window.location.pathname);
    }

    function filterOrderOptions() {
        var $orderFilter = $('#orderFilter');
        if (!$orderFilter.length) return;

        var dealerId = $('#dealerFilter').val() || 'all';
        var current = $orderFilter.val();

        $orderFilter.find('option').each(function () {
            var $opt = $(this);
            if (!$opt.val() || $opt.val() === 'all') {
                $opt.prop('disabled', false);
                return;
            }
            var visible = dealerId === 'all' || String($opt.data('dealer-id')) === String(dealerId);
            $opt.prop('disabled', !visible);
        });

        if (current && current !== 'all') {
            var $selected = $orderFilter.find('option[value="' + current + '"]');
            if ($selected.length && $selected.prop('disabled')) {
                $orderFilter.val('all');
            }
        }

        $orderFilter.trigger('change.select2');
    }

    filterOrderOptions();

    var dispatch_table = $('#dispatch_table').DataTable({
        pageLength  : 10,
        deferRender : true,
        processing  : true,
        serverSide  : true,
        responsive  : true,
        dom         : 'lrtip',
        order       : [[0, 'desc']],

        ajax: {
            url  : "{{ route('dispatch.index') }}",
            data : function (d) {
                d.date_from  = $('#dateFrom').val() || '';
                d.date_to    = $('#dateTo').val() || '';
                d.dealer_id  = $('#dealerFilter').val() || 'all';
                d.order_id   = $('#orderFilter').val() || 'all';
                d.product_id = $('#productFilter').val() || 'all';
            }
        },

        columns: [
            { data: 'id',              name: 'id',              visible: false,  orderable: false, searchable: false },
            { data: 'DT_RowIndex',     name: 'DT_RowIndex',                      orderable: false, searchable: false },
            { data: 'unique_order_id', name: 'unique_order_id',                  orderable: false,  searchable: false },
            { data: 'product_name',    name: 'product_name',                     orderable: false, searchable: false },
            { data: 'no_of_bags',      name: 'no_of_bags',                       orderable: false,  searchable: false },
            { data: 'dealer_name',     name: 'dealer_name',                      orderable: false, searchable: false },
            { data: 'dispatch_date',   name: 'dispatch_date',                    orderable: false,  searchable: false },
            { data: 'transporter_name',name: 'transporter_name',                 orderable: false, searchable: false },
            { data: 'truck_number',    name: 'truck_number',                     orderable: false,  searchable: false },
            { data: 'driver_contact',  name: 'driver_contact',                   orderable: false,  searchable: false },
            { data: 'late_fee',        name: 'late_fee',        className: 'text-end', orderable: false, searchable: false },
            { data: 'balance_due',     name: 'balance_due',     className: 'text-end', orderable: false, searchable: false },
            { data: 'status',          name: 'status',                           orderable: false,  searchable: false },
            { data: 'action',          name: 'action',          className: 'text-center',
                                                                                 orderable: false, searchable: false },
        ],

        createdRow: function (row, data) {
            if (data.is_complete) {
                $(row).addClass('dispatch-row-complete');
            }
        },

        language: {
            processing: '<div class="text-primary">Loading…</div>',
            emptyTable: 'No dispatch records found.',
            zeroRecords: 'No matching records found.',
        },
    });

    $('#dealerFilter, #orderFilter, #productFilter').on('change', function () {
        if (this.id === 'dealerFilter') {
            filterOrderOptions();
        }
        syncFilterUrl();
        dispatch_table.draw();
    });

    var dateFromPicker = flatpickr('#dateFrom', {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd-m-Y',
        allowInput: true,
        onChange: function () {
            syncFilterUrl();
            dispatch_table.draw();
        },
    });

    var dateToPicker = flatpickr('#dateTo', {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd-m-Y',
        allowInput: true,
        onChange: function () {
            syncFilterUrl();
            dispatch_table.draw();
        },
    });

    $('#resetDispatchFilters').on('click', function () {
        dateFromPicker.clear();
        dateToPicker.clear();

        if ($('#dealerFilter').length) {
            $('#dealerFilter').val('all');
        }
        $('#orderFilter, #productFilter').val('all');
        $('.search-dropdown').trigger('change.select2');

        filterOrderOptions();
        syncFilterUrl();
        dispatch_table.draw();
    });

});
</script>
@endsection
