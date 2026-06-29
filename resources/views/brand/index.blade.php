@extends('layouts.main')
@section('content')
@section('title')
    {{ $page_title }}
@endsection
<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-sm-4">
                <div class="icon-form mb-3 mb-sm-0">
                    <span class="form-icon"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" id="customSearch" placeholder="Search">
                </div>
            </div>
            <div class="col-sm-8">
                <div class="d-flex align-items-center flex-wrap row-gap-2 justify-content-sm-end">
                    @can('add-brand')
                    <a href="javascript:void(0);" class="btn btn-primary" id="openModal" data-bs-toggle="modal"
                        data-bs-target="#brandModal">
                        <i class="ti ti-square-rounded-plus me-2"></i>Add Brand
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive custom-table">
            <table class="table dataTable no-footer" id="brand_table">
                <button class="btn btn-primary" id="bulk_delete_button" style="display: none;">
                    <i class="ti ti-trash me-1"></i>Delete Selected
                </button>
                <thead class="thead-light">
                    <tr>
                        <th hidden>ID</th>
                        <th class="no-sort" scope="col">
                            <label class="checkboxs">
                                <input type="checkbox" id="select-all" class="brand_checkbox">
                                <span class="checkmarks"></span>
                            </label>
                        </th>
                        <th class="no-sort" scope="col">Sr no</th>
                        <th scope="col">Brand Name</th>
                        <th scope="col">Status</th>
                        @canany(['edit-brand', 'delete-brand'])
                        <th scope="col">Action</th>
                        @endcanany
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal custom-modal fade" id="brandModal" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Brand Management</h5>
                <div class="d-flex align-items-center mod-toggle">
                    <button type="button" class="btn-close close_poup" data-bs-dismiss="modal">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
            </div>
            <form id="brandForm">
                @csrf
                <input type="hidden" name="brand_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="col-form-label">Brand Name *</label>
                        <input type="text" name="name" value="" class="form-control" placeholder="Brand Name">
                        <span class="name_error"></span>
                    </div>

                    <div class="mb-3">
                        <label class="col-form-label">Status</label>
                        <div class="d-flex align-items-center">
                            <div class="me-2">
                                <input type="radio" class="status-radio" id="active1" name="status" value="1"
                                    {{ old('status', '1') == '1' ? 'checked' : '' }}>
                                <label for="active1">Active</label>
                            </div>
                            <div>
                                <input type="radio" class="status-radio" id="inactive1" name="status" value="0"
                                    {{ old('status') == '0' ? 'checked' : '' }}>
                                <label for="inactive1">Inactive</label>
                            </div>
                        </div>
                        @error('status')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="d-flex align-items-center justify-content-end m-0">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('datatable-scripts')
    @include('partials.datatable-scripts')
@endpush

@section('script')
<script>
withDataTable(function () {
    const isShowAction = {{ auth()->user()->canAny(['edit-brand', 'delete-brand']) ? 'true' : 'false' }};
    const isShowCheckbox = {{ auth()->user()->can('delete-brand') ? 'true' : 'false' }};

    var brandAjax = buildDataTableAjax("{{ route('brand.index') }}");
    var brand_table = $('#brand_table').DataTable({
        pageLength: 10,
        deferRender: true,
        processing: true,
        serverSide: true,
        responsive: true,
        dom: 'lrtip',
        order: [[0, 'asc']],
        ajax: brandAjax,
        columns: [
            { data: 'id', name: 'id', visible: false, searchable: false },
            { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false, visible: isShowCheckbox },
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'name', name: 'name', searchable: true },
            { data: 'status', name: 'status', searchable: true },
            { data: 'action', name: 'action', orderable: false, searchable: false, visible: isShowAction },
        ],
    });
    brandAjax._bindTable(brand_table);

    bindDebouncedDataTableSearch('#customSearch', brand_table);

    $('#openModal').click(function() {
        $('#brandForm')[0].reset();
        $('#modalTitle').text('Brand Management');
        $('#submitBtn').text('Create');
        $('input[name="brand_id"]').val('');
        $('#brandModal').modal('show');
        $('#brandForm .text-danger').text('');
        $('#brandForm').find('.is-invalid').removeClass('is-invalid');
        $('input[name="status"][value="1"]').prop('checked', true);
    });

    $(document).on('click', '.edit-btn', function() {
        let brand_id = $(this).data('id');
        $('#brandForm .text-danger').text('');
        $('#brandForm').find('.is-invalid').removeClass('is-invalid');

        $.get('{{ route('brand.edit', ':id') }}'.replace(':id', brand_id), function(brand) {
            $('#modalTitle').text('Edit Brand Management');
            $('#submitBtn').text('Update');
            $('input[name="brand_id"]').val(brand_id);
            $('input[name="name"]').val(brand.name);
            $('input[name="status"][value="' + brand.status + '"]').prop('checked', true);
            $('#brandModal').modal('show');
        });
    });

    $('#brandForm').submit(function(e) {
        e.preventDefault();
        let brand_id = $('input[name="brand_id"]').val();
        let url = brand_id
            ? '{{ route('brand.update', ':id') }}'.replace(':id', brand_id)
            : "{{ route('brand.store') }}";
        let method = brand_id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: $(this).serialize() + '&_method=' + method,
            success: function(response) {
                $('#brandModal').modal('hide');
                brand_table.ajax.reload();
                show_success(response.message);
            },
            error: function(response) {
                display_errors(response.responseJSON.errors);
            }
        });
    });

    $(document).on('click', '.deleteBrand', function(event) {
        event.preventDefault();
        let brandId = $(this).data('id');
        let form = $('#delete-form-' + brandId);
        confirmDeletion(function() {
            form.submit();
        });
    });

    function display_errors(errors) {
        $('#brandForm .error-text').text('');
        $.each(errors, function(key, value) {
            $('input[name=' + key + ']').addClass('is-invalid');
            $('.' + key + '_error').text(value[0]).addClass('text-danger');
        });
    }

    $('#select-all').change(function() {
        $('.brand_checkbox').prop('checked', this.checked);
    });

    $(document).on('change', '.brand_checkbox', function() {
        let count = $('.brand_checkbox:checked').length;
        $('#bulk_delete_button').toggle(count > 0);
    });

    $('#bulk_delete_button').click(function() {
        confirmDeletion(function() {
            var selectedIds = $('.brand_checkbox:checked').map(function() {
                return $(this).data('id');
            }).get();

            if (selectedIds.length > 0) {
                $.ajax({
                    url: "{{ route('brand.bulkDelete') }}",
                    method: 'POST',
                    data: {
                        ids: selectedIds,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        show_success(response.message);
                        brand_table.ajax.reload();
                        $('#bulk_delete_button').hide();
                    },
                    error: function() {
                        show_error('An error occurred while deleting.');
                    }
                });
            } else {
                alert('No items selected.');
            }
        });
    });

    function confirmDeletion(callback) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'You want to remove this brand?',
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
            if (result.isConfirmed) {
                callback();
            }
        });
    }
});
</script>
@endsection
