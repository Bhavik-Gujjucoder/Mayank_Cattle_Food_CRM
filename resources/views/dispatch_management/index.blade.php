@extends('layouts.main')
@section('title')
    {{-- {{ $page_title }} --}}
@endsection
@section('content')

<div class="card">

    {{-- ══════════════════════════════════════════════════════════════
         HEADER — title + Order filter
    ══════════════════════════════════════════════════════════════ --}}
    <div class="card-header">
        <div class="row align-items-center g-3">

            {{-- Page title chip --}}
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

            {{-- Search --}}
            {{-- <div class="col-12 col-sm-4 col-lg-3">
                <div class="icon-form">
                    <span class="form-icon"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" id="dispatchSearch"
                           placeholder="Search truck, driver…">
                </div>
            </div> --}}

            {{-- Order Number filter --}}
            <div class="col-12 col-sm-4 col-lg-3">
                <select class="form-select select" id="orderFilter" name="order_id">
                    <option value="all">All Orders</option>
                    @foreach ($orders as $order)
                        <option value="{{ $order->id }}">{{ $order->unique_order_id }}</option>
                    @endforeach
                </select>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         TABLE
    ══════════════════════════════════════════════════════════════ --}}
    <div class="card-body">
        <div class="table-responsive custom-table">
            <table class="table dataTable no-footer" id="dispatch_table">
                <thead class="thead-light">
                    <tr>
                        <th hidden>ID</th>
                        <th class="no-sort" style="width:60px;">Sr No</th>
                        <th>Order ID</th>
                        <th>Product</th>
                        <th style="width:130px;">Bags / Ton</th>
                        <th>Dealer Name</th>
                        <th style="width:130px;">Dispatch Date</th>
                        <th>Transport</th>
                        <th>Truck Number</th>
                        <th>Driver Contact</th>
                        {{-- <th >is_complete</th> --}}
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

    /* ── Order filter — Select2 ────────────────────────────────── */
    $('#orderFilter').select2({
        placeholder: 'Filter by Order…',
        width      : '100%',
    });

    /* ── DataTable ─────────────────────────────────────────────── */
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
                d.order_id = $('#orderFilter').val() || 'all';
            }
        },

        columns: [
            /*
             * orderable / searchable rules for server-side DataTables:
             *   TRUE  → only for real columns that exist on dispatch_management table
             *   FALSE → for computed values (DT_RowIndex, addColumn relationships, action HTML)
             *           Yajra would try ORDER BY / WHERE on these → SQL error or wrong results
             */
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
            // { data: 'is_complete',     name: 'is_complete',     visible: true,  orderable: false, searchable: false },
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

    /* ── Redraw when order filter changes ─────────────────────── */
    $('#orderFilter').on('change', function () {
        dispatch_table.draw();
    });

    /* ── Live search ───────────────────────────────────────────── */
    $('#dispatchSearch').on('keyup', function () {
        dispatch_table.search(this.value).draw();
    });

});
</script>
@endsection
