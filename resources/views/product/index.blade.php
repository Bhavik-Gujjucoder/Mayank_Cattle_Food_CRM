@extends('layouts.main')
@section('content')
@section('title')
    {{ $page_title }}
@endsection

<div class="card">
    <div class="card-header">
        <!-- Search -->
        <div class="row align-items-center">
            <div class="col-sm-4">
                <div class="icon-form mb-3 mb-sm-0">
                    <span class="form-icon"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" id="customSearch" placeholder="Search">
                </div>
            </div>
            <div class="col-sm-8">
                <div class="d-flex align-items-center flex-wrap row-gap-2 justify-content-sm-end">
                    @can('add-product')
                        <a href="javascript:void(0);" class="btn btn-primary" id="openModal">
                            <i class="ti ti-square-rounded-plus me-2"></i>Add Product
                        </a>
                    @endcan
                </div>
            </div>
        </div>
        <!-- /Search -->
    </div>
    <div class="card-body">
        <div class="table-responsive custom-table">
            <table class="table dataTable no-footer" id="product_table">
                <button class="btn btn-primary" id="bulk_delete_button" style="display: none;"><i class="ti ti-trash me-2"></i>Delete Selected</button>
                <thead class="thead-light">
                    <tr>
                        <th hidden>ID</th>
                        <th class="no-sort" scope="col">
                            <label class="checkboxs">
                                <input type="checkbox" id="select-all" class="product_checkbox">
                                <span class="checkmarks"></span>
                            </label>
                        </th>
                        <th class="no-sort" scope="col">Sr no</th>
                        <th scope="col">Product Name</th>
                        <th scope="col">Status</th>
                        @canany(['edit-product', 'delete-product'])
                            <th class="" scope="col">Action</th>
                        @endcanany
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Add / Edit Modal -->
<div class="modal custom-modal fade" id="adminModal" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Product Management</h5>
                <div class="d-flex align-items-center mod-toggle">
                    <button type="button" class="btn-close close_poup" data-bs-dismiss="modal">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
            </div>
            <form id="adminForm">
                @csrf
                <input type="hidden" name="product_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="col-form-label">Product Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="Product Name">
                        <span class="name_error"></span>
                    </div>
                    <div class="mb-3">
                        <label class="col-form-label">Status</label>
                        <div class="d-flex align-items-center">
                            <div class="me-2">
                                <input type="radio" class="status-radio" id="active1" name="status" value="1" checked>
                                <label for="active1">Active</label>
                            </div>
                            <div>
                                <input type="radio" class="status-radio" id="inactive1" name="status" value="0">
                                <label for="inactive1">Inactive</label>
                            </div>
                        </div>
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
@section('script')
<script>
    const isShowAction ={{ auth()->user()->canAny(['edit-product', 'delete-product'])? 'true': 'false' }};
    const isShowCheckbox ={{ auth()->user()->can('delete-product')? 'true': 'false' }};
    var product_table = $('#product_table').DataTable({
        pageLength: 10,
        deferRender: true,
        processing: true,
        serverSide: true,
        responsive: true,
        dom: 'lrtip',
        order: [[0, 'desc']],
        ajax: "{{ route('product.index') }}",
        columns: [
            { data: 'id', name: 'id', visible: false, searchable: false },
            { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false, visible: isShowCheckbox },
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'name', name: 'name', searchable: true },
            { data: 'status', name: 'status', searchable: true },
            { data: 'action', name: 'action', orderable: false, searchable: false, visible: isShowAction },
        ]
    });

    // Custom Search Box
    $('#customSearch').on('keyup', function() {
        product_table.search(this.value).draw();
    });

    // Open modal for adding
    $('#openModal').click(function() {
        $('#adminForm')[0].reset();
        $('#modalTitle').text('Add Product');
        $('#submitBtn').text('Create');
        $('input[name="product_id"]').val('');
        $('input[name="status"][value="1"]').prop('checked', true);
        $("#adminForm .text-danger").text('');
        $('#adminForm').find('.is-invalid').removeClass('is-invalid');
        $('#adminModal').modal('show');
    });

    // Open modal for editing
    $(document).on('click', '.edit-btn', function() {
        let product_id = $(this).data('id');
        $("#adminForm .text-danger").text('');
        $('#adminForm').find('.is-invalid').removeClass('is-invalid');

        $.get('{{ route('product.edit', ':id') }}'.replace(':id', product_id), function(product) {
            $('#modalTitle').text('Edit Product');
            $('#submitBtn').text('Update');
            $('input[name="product_id"]').val(product_id);
            $('input[name="name"]').val(product.name);
            $('input[name="status"][value="' + product.status + '"]').prop('checked', true);
            $('#adminModal').modal('show');
        });
    });

    // Handle form submission (add & edit)
    $('#adminForm').submit(function(e) {
        e.preventDefault();
        $("#adminForm .text-danger").text('');
        $("#adminForm .error-text").text('');
        let product_id = $('input[name="product_id"]').val();
        let url = product_id
            ? '{{ route('product.update', ':id') }}'.replace(':id', product_id)
            : '{{ route('product.store') }}';
        let method = product_id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: $(this).serialize() + '&_method=' + method,
            success: function(response) {
                $('#adminModal').modal('hide');
                product_table.ajax.reload();
                show_success(response.message);
            },
            error: function(response) {
                display_errors(response.responseJSON.errors);
            }
        });
    });

    // Delete single
    $(document).on('click', '.deleteProduct', function(event) {
        event.preventDefault();
        let productId = $(this).data('id');
        let form = $('#delete-form-' + productId);
        confirmDeletion(function() {
            form.submit();
        });
    });

    function display_errors(errors) {
        $("#adminForm .error-text").text('');
        $.each(errors, function(key, value) {
            $('input[name=' + key + ']').addClass('is-invalid');
            $('.' + key + '_error').text(value[0]).addClass('text-danger');
        });
    }

    // Bulk delete — select all
    $('#select-all').change(function() {
        $('.product_checkbox').prop('checked', this.checked);
    });

    $(document).on('change', '.product_checkbox', function() {
        let count = $('.product_checkbox:checked').length;
        if (count > 0) {
            $('#bulk_delete_button').show();
        } else {
            $('#bulk_delete_button').hide();
        }
    });

    $('#bulk_delete_button').click(function() {
        confirmDeletion(function() {
            var selectedIds = $('.product_checkbox:checked').map(function() {
                return $(this).data('id');
            }).get();

            if (selectedIds.length > 0) {
                $.ajax({
                    url: "{{ route('product.bulkDelete') }}",
                    method: 'POST',
                    data: { ids: selectedIds, _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        show_success(response.message);
                        product_table.ajax.reload();
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
            text: 'You want to remove this Product? Once deleted, it cannot be recovered.',
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
</script>
@endsection
