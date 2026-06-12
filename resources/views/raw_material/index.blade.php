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
                        <input type="text" class="form-control" id="customSearch" placeholder="Search Materials">
                    </div>
                </div>
                <div class="common-hed-form cls-form-select-input">
                    <label class="col-form-label">Status</label>
                    <select class="form-select select search-dropdown" id="statusFilter">
                        <option value="all">All</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="common-hed-form cls-form-select-input d-flex align-items-end">
                    <button type="button" class="btn btn-light" id="resetMaterialFilters">
                        <i class="ti ti-refresh me-1"></i>Reset
                    </button>
                </div>
            </div>
            <div class="cls-form-right">
                <div class="comm-header-right-btn">
                    @can('export-raw-material-inventory')
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="exportMaterialsBtn">
                                <i class="ti ti-file-export me-2"></i>Export (0)
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item export-filtered-link" href="#" data-export-url="{{ route('raw-material.export') }}">
                                        <i class="ti ti-file-spreadsheet me-2"></i>Export Excel
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item export-filtered-link" href="#" data-export-url="{{ route('raw-material.export-list-pdf') }}">
                                        <i class="ti ti-file-type-pdf me-2"></i>Export PDF
                                    </a>
                                </li>
                            </ul>
                        </div>
                    @endcan
                    @can('add-raw-material-inventory')
                        <a href="{{ route('raw-material.create') }}" class="btn btn-primary">
                            <i class="ti ti-square-rounded-plus me-2"></i>Add Raw Material
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive custom-table">
            <table class="table dataTable no-footer" id="raw_material_table">
                <thead class="thead-light">
                    <tr>
                        <th hidden>ID</th>
                        <th class="no-sort" scope="col">Sr No</th>
                        <th scope="col">Material ID</th>
                        <th scope="col">Category</th>
                        <th scope="col">Name</th>
                        <th scope="col">Unit</th>
                        <th scope="col">Total Stock</th>
                        <th scope="col">Available Stock</th>
                        <th scope="col">Last Price/kg</th>
                        <th scope="col">Avg Price/kg</th>
                        <th scope="col">Status</th>
                        @canany(['edit-raw-material-inventory', 'delete-raw-material-inventory'])
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

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('status')) $('#statusFilter').val(urlParams.get('status'));
    const isShowAction = {{ auth()->user()->canAny(['edit-raw-material-inventory', 'delete-raw-material-inventory']) ? 'true' : 'false' }};

    var raw_material_table = $('#raw_material_table').DataTable({
        pageLength: 10,
        deferRender: true,
        processing: true,
        serverSide: true,
        responsive: true,
        dom: 'lrtip',
        order: [[0, 'desc']],
        ajax: {
            url: "{{ route('raw-material.index') }}",
            data: function (d) {
                d.status = $('#statusFilter').val();
            }
        },
        columns: [
            { data: 'id', name: 'id', visible: false, searchable: false },
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'raw_material_unique_id', name: 'raw_material_unique_id', searchable: true },
            { data: 'category_name', name: 'category_name', orderable: false, searchable: false },
            { data: 'name', name: 'name', searchable: true },
            { data: 'unit', name: 'unit', searchable: false },
            { data: 'total_stock', name: 'total_stock', searchable: false },
            { data: 'available_stock', name: 'available_stock', searchable: false },
            { data: 'last_purchase_price', name: 'last_purchase_price', searchable: false },
            { data: 'average_price', name: 'average_price', searchable: false },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false, visible: isShowAction },
        ],
        drawCallback: function () {
            var n = raw_material_table.page.info().recordsDisplay;
            if ($('#exportMaterialsBtn').length) {
                $('#exportMaterialsBtn').html('<i class="ti ti-file-export me-2"></i>Export (' + n + ')');
            }
        }
    });

    $('#statusFilter').on('change', function () {
        const p = new URLSearchParams();
        const v = $(this).val();
        if (v && v !== 'all') p.set('status', v);
        const qs = p.toString();
        window.history.replaceState({}, '', qs ? ('?' + qs) : window.location.pathname);
        raw_material_table.draw();
    });

    bindDebouncedDataTableSearch('#customSearch', raw_material_table);

    $('#resetMaterialFilters').on('click', function () {
        $('#customSearch').val('');
        raw_material_table.search('');
        $('#statusFilter').val('all').trigger('change.select2');
        window.history.replaceState({}, '', window.location.pathname);
        raw_material_table.draw();
    });

    function buildFilterQueryString() {
        const params = new URLSearchParams();
        const status = $('#statusFilter').val();
        if (status && status !== 'all') params.set('status', status);
        const search = raw_material_table.search();
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

    $(document).on('click', '.toggle-status-btn', function (e) {
        e.preventDefault();
        var url = $(this).data('url');
        Swal.fire({
            title: 'Toggle Status?',
            text: 'This will activate or deactivate the raw material.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel',
            customClass: {
                popup: 'my-custom-popup',
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-secondary',
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                var $form = $('<form>', { method: 'POST', action: url });
                $form.append($('<input>', { type: 'hidden', name: '_token', value: '{{ csrf_token() }}' }));
                $form.append($('<input>', { type: 'hidden', name: '_method', value: 'PATCH' }));
                $('body').append($form);
                $form.submit();
            }
        });
    });

    function confirmDeletion(callback) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'You want to remove this raw material? Once deleted, it cannot be recovered.',
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
        }).then(function (result) {
            if (result.isConfirmed) callback();
        });
    }
});
</script>
@endsection
