@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('styles')
    @include('raw_material.partials.module-responsive')
@endsection
@section('content')

<div class="card raw-material-module">
    <div class="card-header">
        <div class="cls-cardhed-part">
            <div class="cls-form-left">
                <div class="common-hed-form cls-form-serc">
                    <div class="icon-form">
                        <span class="form-icon"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" id="customSearch" placeholder="Search">
                    </div>
                </div>
                <div class="common-hed-form cls-form-select-input">
                    <label class="col-form-label">Status</label>
                    <select class="form-select select search-dropdown" id="statusFilter">
                        <option value="all">All</option>
                        <option value="0">On Road</option>
                        <option value="1">Received</option>
                        <option value="2">Cancelled</option>
                    </select>
                </div>
                <div class="common-hed-form cls-form-select-input">
                    <label class="col-form-label">Material</label>
                    <select class="form-select select search-dropdown" id="materialFilter">
                        <option value="all">All Materials</option>
                        @foreach ($raw_materials as $material)
                            <option value="{{ $material->id }}">{{ $material->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="common-hed-form cls-form-select-input">
                    <label class="col-form-label">Order</label>
                    <select class="form-select select search-dropdown" id="orderFilter">
                        <option value="all">All Orders</option>
                        @foreach ($orders as $order)
                            <option value="{{ $order->id }}">@include('raw_material.partials.order-select-label', ['order' => $order])</option>
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
                <div class="common-hed-form cls-form-select-input d-flex align-items-end">
                    <button type="button" class="btn btn-light" id="resetReceiveFilters">
                        <i class="ti ti-refresh me-1"></i>Reset
                    </button>
                </div>
            </div>
            <div class="cls-form-right">
                <div class="comm-header-right-btn">
                    @can('export-raw-material-receive')
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="exportReceivesBtn">
                                <i class="ti ti-file-export me-2"></i>Export (0)
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item export-filtered-link" href="#" data-export-url="{{ route('raw-material.receive.export') }}">
                                        <i class="ti ti-file-spreadsheet me-2"></i>Export Excel
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item export-filtered-link" href="#" data-export-url="{{ route('raw-material.receive.export-list-pdf') }}">
                                        <i class="ti ti-file-type-pdf me-2"></i>Export PDF
                                    </a>
                                </li>
                            </ul>
                        </div>
                    @endcan
                    @can('add-raw-material-receive')
                        <a href="{{ route('raw-material.receive.create') }}" class="btn btn-primary">
                            <i class="ti ti-square-rounded-plus me-2"></i>Add Received Entry
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive custom-table">
            <table class="table dataTable no-footer" id="raw_material_receive_table">
                <thead class="thead-light">
                    <tr>
                        <th hidden>ID</th>
                        <th class="no-sort" scope="col">Sr No</th>
                        <th scope="col">Order ID</th>
                        <th scope="col">Supplier Order ID</th>
                        <th scope="col">Category</th>
                        <th scope="col">Material</th>
                        <th scope="col">Qty (tons)</th>
                        <th scope="col">Freight</th>
                        <th scope="col">Received Date</th>
                        <th scope="col">Status</th>
                        @canany(['edit-raw-material-receive', 'delete-raw-material-receive'])
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

    const filterParams = { status: '#statusFilter', raw_material_id: '#materialFilter', raw_material_order_id: '#orderFilter', date_from: '#dateFrom', date_to: '#dateTo' };
    const urlParams = new URLSearchParams(window.location.search);
    Object.entries(filterParams).forEach(function ([key, selector]) {
        if (urlParams.has(key)) $(selector).val(urlParams.get(key)).trigger('change.select2');
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

    const isShowAction = {{ auth()->user()->canAny(['edit-raw-material-receive', 'delete-raw-material-receive']) ? 'true' : 'false' }};

    var raw_material_receive_table = $('#raw_material_receive_table').DataTable({
        pageLength: 10,
        deferRender: true,
        processing: true,
        serverSide: true,
        responsive: true,
        dom: 'lrtip',
        order: [[0, 'desc']],
        ajax: {
            url: "{{ route('raw-material.receive.index') }}",
            data: function (d) {
                d.status = $('#statusFilter').val();
                d.raw_material_id = $('#materialFilter').val();
                d.raw_material_order_id = $('#orderFilter').val();
                d.date_from = $('#dateFrom').val();
                d.date_to = $('#dateTo').val();
            }
        },
        columns: [
            { data: 'id', name: 'id', visible: false, searchable: false },
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'order_unique_id', name: 'order_unique_id', orderable: false, searchable: false },
            { data: 'supplier_order_id', name: 'supplier_order_id', orderable: false, searchable: false },
            { data: 'category_name', name: 'category_name', orderable: false, searchable: false },
            { data: 'material_name', name: 'material_name', orderable: false, searchable: false },
            { data: 'qty', name: 'qty', searchable: false },
            { data: 'freight', name: 'freight', searchable: false },
            { data: 'received_date', name: 'received_date', searchable: false },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false, visible: isShowAction },
        ],
        drawCallback: function () {
            var n = raw_material_receive_table.page.info().recordsDisplay;
            if ($('#exportReceivesBtn').length) {
                $('#exportReceivesBtn').html('<i class="ti ti-file-export me-2"></i>Export (' + n + ')');
            }
        }
    });

    var dateFromPicker = flatpickr('#dateFrom', {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd-m-Y',
        allowInput: true,
        onChange: function () {
            syncFilterUrl();
            raw_material_receive_table.draw();
        }
    });

    var dateToPicker = flatpickr('#dateTo', {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd-m-Y',
        allowInput: true,
        onChange: function () {
            syncFilterUrl();
            raw_material_receive_table.draw();
        }
    });

    $('#resetReceiveFilters').on('click', function () {
        $('#customSearch').val('');
        raw_material_receive_table.search('');
        dateFromPicker.clear();
        dateToPicker.clear();
        $('#statusFilter, #materialFilter, #orderFilter').val('all');
        $('.search-dropdown').trigger('change.select2');
        syncFilterUrl();
        raw_material_receive_table.draw();
    });

    $('#statusFilter, #materialFilter, #orderFilter').on('change', function () {
        syncFilterUrl();
        raw_material_receive_table.draw();
    });

    bindDebouncedDataTableSearch('#customSearch', raw_material_receive_table);

    function buildFilterQueryString() {
        const params = new URLSearchParams();
        Object.entries(filterParams).forEach(function ([key, selector]) {
            const v = $(selector).val();
            if (v && v !== 'all') params.set(key, v);
        });
        const search = raw_material_receive_table.search();
        if (search) params.set('search', search);
        return params.toString();
    }

    $(document).on('click', '.export-filtered-link', function (e) {
        e.preventDefault();
        const qs = buildFilterQueryString();
        window.location.href = $(this).data('export-url') + (qs ? ('?' + qs) : '');
    });

    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        confirmDeletion(function () {
            $('#delete-form-' + id).submit();
        });
    });

    $(document).on('click', '.mark-received-btn', function (e) {
        e.preventDefault();
        var url = $(this).data('url');
        Swal.fire({
            title: 'Mark as Received?',
            text: 'This will update stock for this material.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, mark received',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-secondary',
            }
        }).then(function (result) {
            if (result.isConfirmed) submitPatchForm(url);
        });
    });

    $(document).on('click', '.cancel-receive-btn', function (e) {
        e.preventDefault();
        var url = $(this).data('url');
        Swal.fire({
            title: 'Cancel Entry?',
            text: 'This receive entry will be marked as cancelled.',
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
            text: 'You want to remove this receive entry? Once deleted, it cannot be recovered.',
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
