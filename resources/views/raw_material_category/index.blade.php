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
                            <input type="text" class="form-control" id="customSearch" placeholder="Search Categories">
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
                        <button type="button" class="btn btn-danger" id="resetCategoryFilters">
                            <i class="ti ti-refresh me-1"></i>Reset
                        </button>
                    </div>
                </div>
                <div class="cls-form-right">
                    <div class="comm-header-right-btn">
                        @can('export-raw-material-category')
                            <div class="btn-group me-2">
                                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown"
                                    aria-expanded="false" id="exportCategoriesBtn">
                                    <i class="ti ti-file-export me-2"></i>Export (0)
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item export-filtered-link" href="#"
                                            data-export-url="{{ route('raw-material.category.export') }}">
                                            <i class="ti ti-file-spreadsheet me-2"></i>Export Excel
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item export-filtered-link" href="#"
                                            data-export-url="{{ route('raw-material.category.export-list-pdf') }}">
                                            <i class="ti ti-file-type-pdf me-2"></i>Export PDF
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        @endcan
                        @can('add-raw-material-category')
                            <a href="{{ route('raw-material.category.create') }}" class="btn btn-primary">
                                <i class="ti ti-square-rounded-plus me-2"></i>Add Category
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive custom-table">
                <table class="table dataTable no-footer" id="raw_material_category_table">
                    <thead class="thead-light">
                        <tr>
                            <th hidden>ID</th>
                            <th class="no-sort" scope="col">Sr No</th>
                            <th scope="col">Category ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Status</th>
                            @canany(['edit-raw-material-category', 'delete-raw-material-category'])
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
        $(document).ready(function() {
            $('.search-dropdown').select2({
                placeholder: 'Select',
                width: '100%'
            });

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('status')) $('#statusFilter').val(urlParams.get('status'));
            const isShowAction =
                {{ auth()->user()->canAny(['edit-raw-material-category', 'delete-raw-material-category'])? 'true': 'false' }};

            var category_table = $('#raw_material_category_table').DataTable({
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
                    url: "{{ route('raw-material.category.index') }}",
                    data: function(d) {
                        d.status = $('#statusFilter').val();
                    }
                },
                columns: [{
                        data: 'id',
                        name: 'id',
                        visible: false,
                        searchable: false
                    },
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'category_unique_id',
                        name: 'category_unique_id',
                        searchable: true
                    },
                    {
                        data: 'name',
                        name: 'name',
                        searchable: true
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        visible: isShowAction
                    },
                ],
                drawCallback: function() {
                    var n = category_table.page.info().recordsDisplay;
                    if ($('#exportCategoriesBtn').length) {
                        $('#exportCategoriesBtn').html(
                            '<i class="ti ti-file-export me-2"></i>Export (' + n + ')');
                    }
                }
            });

            $('#statusFilter').on('change', function() {
                const p = new URLSearchParams();
                const v = $(this).val();
                if (v && v !== 'all') p.set('status', v);
                const qs = p.toString();
                window.history.replaceState({}, '', qs ? ('?' + qs) : window.location.pathname);
                category_table.draw();
            });

            bindDebouncedDataTableSearch('#customSearch', category_table);

            $('#resetCategoryFilters').on('click', function() {
                $('#customSearch').val('');
                category_table.search('');
                $('#statusFilter').val('all').trigger('change.select2');
                window.history.replaceState({}, '', window.location.pathname);
                category_table.draw();
            });

            function buildFilterQueryString() {
                const params = new URLSearchParams();
                const status = $('#statusFilter').val();
                if (status && status !== 'all') params.set('status', status);
                const search = category_table.search();
                if (search) params.set('search', search);
                return params.toString();
            }

            $(document).on('click', '.export-filtered-link', function(e) {
                e.preventDefault();
                const qs = buildFilterQueryString();
                window.location.href = $(this).data('export-url') + (qs ? ('?' + qs) : '');
            });

            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You want to remove this category?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'btn btn-primary',
                        cancelButton: 'btn btn-secondary'
                    }
                }).then(function(result) {
                    if (result.isConfirmed) $('#delete-form-' + id).submit();
                });
            });

            $(document).on('click', '.toggle-status-btn', function(e) {
                e.preventDefault();
                var url = $(this).data('url');
                Swal.fire({
                    title: 'Toggle Status?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, proceed',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'btn btn-primary',
                        cancelButton: 'btn btn-secondary'
                    }
                }).then(function(result) {
                    if (result.isConfirmed) {
                        var $form = $('<form>', {
                            method: 'POST',
                            action: url
                        });
                        $form.append($('<input>', {
                            type: 'hidden',
                            name: '_token',
                            value: '{{ csrf_token() }}'
                        }));
                        $form.append($('<input>', {
                            type: 'hidden',
                            name: '_method',
                            value: 'PATCH'
                        }));
                        $('body').append($form);
                        $form.submit();
                    }
                });
            });
        });
    </script>
@endsection
