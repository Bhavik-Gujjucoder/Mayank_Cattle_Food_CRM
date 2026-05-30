@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('content')

<div class="card">
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
                    <label class="col-form-label">Status</label>
                    <select class="form-select select search-dropdown" id="statusFilter">
                        <option value="all">All</option>
                        <option value="0">Pending</option>
                        <option value="1">Partially Received</option>
                        <option value="2">Received</option>
                        <option value="3">Cancelled</option>
                    </select>
                </div>
                <div class="common-hed-form cls-form-select-input">
                    <label class="col-form-label">Supplier</label>
                    <select class="form-select select search-dropdown" id="supplierFilter">
                        <option value="all">All Suppliers</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="common-hed-form cls-form-select-input">
                    <label class="col-form-label">From Date</label>
                    <div class="icon-form">
                        <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                        <input type="text" id="dateFrom" class="form-control flatpickr" placeholder="DD-MM-YYYY" autocomplete="off">
                    </div>
                </div>
                <div class="common-hed-form cls-form-select-input">
                    <label class="col-form-label">To Date</label>
                    <div class="icon-form">
                        <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                        <input type="text" id="dateTo" class="form-control flatpickr" placeholder="DD-MM-YYYY" autocomplete="off">
                    </div>
                </div>
            </div>
            <div class="cls-form-right">
                <div class="comm-header-right-btn">
                    @canany(['add-raw-material-purchas-order', 'edit-raw-material-purchas-order', 'delete-raw-material-purchas-order'])
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="exportOrdersBtn">
                                <i class="ti ti-file-export me-2"></i>Export Orders (0)
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item export-filtered-link" href="#" data-export-url="{{ route('raw-material-order.export') }}">
                                        <i class="ti ti-file-spreadsheet me-2"></i>Export Excel
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item export-filtered-link" href="#" data-export-url="{{ route('raw-material-order.export-list-pdf') }}">
                                        <i class="ti ti-file-type-pdf me-2"></i>Export PDF
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ti ti-database-export me-2"></i>Full Export
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="{{ route('raw-material-order.export-full') }}">
                                        <i class="ti ti-file-spreadsheet me-2"></i>Export Excel
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('raw-material-order.export-full-pdf') }}">
                                        <i class="ti ti-file-type-pdf me-2"></i>Export PDF
                                    </a>
                                </li>
                            </ul>
                        </div>
                    @endcanany
                    @can('add-raw-material-purchas-order')
                        <a href="{{ route('raw-material-order.create') }}" class="btn btn-primary">
                            <i class="ti ti-square-rounded-plus me-2"></i>Add Purchase Order
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive custom-table">
            <table class="table dataTable no-footer" id="raw_material_order_table">
                <thead class="thead-light">
                    <tr>
                        <th hidden>ID</th>
                        <th class="no-sort" scope="col">Sr No</th>
                        <th scope="col">Order ID</th>
                        <th scope="col">Supplier</th>
                        <th scope="col">Order Date</th>
                        <th scope="col">Total Qty</th>
                        <th scope="col">Total Price</th>
                        <th scope="col">Total Freight</th>
                        <th scope="col">Status</th>
                        @canany(['add-raw-material-purchas-order', 'edit-raw-material-purchas-order', 'delete-raw-material-purchas-order'])
                            <th scope="col">Action</th>
                        @endcanany
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
    $('.search-dropdown').select2({ placeholder: 'Select', width: '100%' });

    const filterParams = { status: '#statusFilter', supplier_id: '#supplierFilter', date_from: '#dateFrom', date_to: '#dateTo' };
    const urlParams = new URLSearchParams(window.location.search);
    Object.entries(filterParams).forEach(function ([key, selector]) {
        if (urlParams.has(key)) $(selector).val(urlParams.get(key));
    });
    function syncFilterUrl() {
        const p = new URLSearchParams();
        Object.entries(filterParams).forEach(function ([key, selector]) {
            const v = $(selector).val();
            if (v && v !== 'all') p.set(key, v);
        });
        const qs = p.toString();
        window.history.replaceState({}, '', qs ? ('?' + qs) : window.location.pathname);
    }

    const isShowAction = {{ auth()->user()->canAny(['add-raw-material-purchas-order', 'edit-raw-material-purchas-order', 'delete-raw-material-purchas-order']) ? 'true' : 'false' }};

    var raw_material_order_table = $('#raw_material_order_table').DataTable({
        pageLength: 10,
        deferRender: true,
        processing: true,
        serverSide: true,
        responsive: true,
        dom: 'lrtip',
        order: [[0, 'desc']],
        ajax: {
            url: "{{ route('raw-material-order.index') }}",
            data: function (d) {
                d.status = $('#statusFilter').val();
                d.supplier_id = $('#supplierFilter').val();
                d.date_from = $('#dateFrom').val();
                d.date_to = $('#dateTo').val();
            }
        },
        columns: [
            { data: 'id', name: 'id', visible: false, searchable: false },
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'order_unique_id', name: 'order_unique_id', searchable: true },
            { data: 'supplier_name', name: 'supplier_name', orderable: false, searchable: false },
            { data: 'order_date', name: 'order_date', searchable: false },
            { data: 'total_qty', name: 'total_qty', searchable: false },
            { data: 'total_price', name: 'total_price', searchable: false },
            { data: 'total_freight', name: 'total_freight', searchable: false },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false, visible: isShowAction },
        ],
        drawCallback: function () {
            var n = raw_material_order_table.page.info().recordsDisplay;
            if ($('#exportOrdersBtn').length) {
                $('#exportOrdersBtn').html('<i class="ti ti-file-export me-2"></i>Export Orders (' + n + ')');
            }
        }
    });

    $('#statusFilter, #supplierFilter').on('change', function () {
        syncFilterUrl();
        raw_material_order_table.draw();
    });

    $('#customSearch').on('keyup', function () {
        raw_material_order_table.search(this.value).draw();
    });

    function buildFilterQueryString() {
        const params = new URLSearchParams();
        Object.entries(filterParams).forEach(function ([key, selector]) {
            const v = $(selector).val();
            if (v && v !== 'all') params.set(key, v);
        });
        const search = raw_material_order_table.search();
        if (search) params.set('search', search);
        return params.toString();
    }

    $(document).on('click', '.export-filtered-link', function (e) {
        e.preventDefault();
        const qs = buildFilterQueryString();
        window.location.href = $(this).data('export-url') + (qs ? ('?' + qs) : '');
    });

    flatpickr('.flatpickr', {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd-m-Y',
        allowInput: true,
        onChange: function () {
            syncFilterUrl();
            raw_material_order_table.draw();
        }
    });

    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        confirmDeletion(function () {
            $('#delete-form-' + id).submit();
        });
    });

    $(document).on('click', '.cancel-order-btn', function (e) {
        e.preventDefault();
        var url = $(this).data('url');
        Swal.fire({
            title: 'Cancel Order?',
            text: 'This purchase order will be marked as cancelled.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, cancel it',
            cancelButtonText: 'No',
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-secondary',
            }
        }).then(function (result) {
            if (result.isConfirmed) submitPatchForm(url);
        });
    });

    function submitPatchForm(url) {
        var $form = $('<form>', { method: 'POST', action: url });
        $form.append($('<input>', { type: 'hidden', name: '_token', value: '{{ csrf_token() }}' }));
        $form.append($('<input>', { type: 'hidden', name: '_method', value: 'PATCH' }));
        $('body').append($form);
        $form.submit();
    }

    function confirmDeletion(callback) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'You want to remove this purchase order? Once deleted, it cannot be recovered.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            customClass: {
                popup: 'my-custom-popup',
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-secondary',
            }
        }).then(function (result) {
            if (result.isConfirmed) callback();
        });
    }
});
</script>
@endsection
