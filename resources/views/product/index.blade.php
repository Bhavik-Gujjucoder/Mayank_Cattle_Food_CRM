@extends('layouts.main')
@section('content')
@section('title')
    {{ $page_title }}
@endsection

<div class="card">
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
                    <label class="col-form-label">Brand </label>
                    <select class="form-select select search-dropdown" name="brand_id" id="BrandId">
                        <option value="all">All Brand</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="cls-form-right">
                <div class="comm-header-right-btn">
                    @can('add-product')
                        <a href="javascript:void(0);" class="btn btn-primary" id="openModal">
                            <i class="ti ti-square-rounded-plus me-2"></i>Add Product
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
                    <input type="text" class="form-control" id="customSearch" placeholder="Search">
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
            <div class="col-sm-6">
                <div class="d-flex align-items-center flex-wrap row-gap-2 justify-content-sm-end">

                    @can('add-product')
                        <a href="javascript:void(0);" class="btn btn-primary" id="openModal">
                            <i class="ti ti-square-rounded-plus me-2"></i>Add Product
                        </a>
                    @endcan
                </div>
            </div>
        </div> --}}
        <!-- /Search -->
    </div>


    <div class="card-body">
        <div class="table-responsive custom-table">
            <table class="table dataTable no-footer" id="product_table">
                <button class="btn btn-danger me-2" id="bulk_delete_button" style="display: none;">
                    <i class="ti ti-trash me-2"></i>Delete Selected
                </button>
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
                        <th scope="col">Brand</th>
                        <th scope="col">Unit</th>
                        {{-- <th scope="col">Price</th> --}}
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
                    {{-- Product Name --}}
                    <div class="mb-3">
                        <label class="col-form-label">Product Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Product Name">
                        <span class="name_error text-danger small"></span>
                    </div>

                    {{-- Brand --}}
                    <div class="mb-3">
                        <label class="col-form-label">Brand <span class="text-danger">*</span></label>
                        <select name="brand_id" id="modal_brand_id" class="form-select">
                            <option value="">-- Select Brand --</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                        <span class="brand_id_error text-danger small"></span>
                    </div>

                    {{-- Unit --}}
                    <div class="mb-3">
                        <label class="col-form-label">Unit <span class="text-danger">*</span></label>
                        <select name="unit" id="modal_unit" class="form-select">
                            <option value="">-- Select Unit --</option>
                            <option value="Bag">Bag</option>
                            <option value="Ton">Ton</option>
                        </select>
                        <span class="unit_error text-danger small"></span>
                    </div>

                    {{-- Price --}}
                    <div class="mb-3" hidden>
                        <label class="col-form-label">Price (₹) <span class="text-danger">*</span></label>
                        <input type="number" name="price" id="modal_price" class="form-control" placeholder="0.00"
                            min="0" step="0.01">
                        <span class="price_error text-danger small"></span>
                    </div>

                    {{-- Status --}}
                    <div class="mb-3">
                        <label class="col-form-label">Status</label>
                        <div class="d-flex align-items-center">
                            <div class="me-2">
                                <input type="radio" class="status-radio" id="active1" name="status"
                                    value="1" checked>
                                <label for="active1">Active</label>
                            </div>
                            <div>
                                <input type="radio" class="status-radio" id="inactive1" name="status"
                                    value="0">
                                <label for="inactive1">Inactive</label>
                            </div>
                        </div>
                        <span class="status_error text-danger small"></span>
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
    const isShowAction = {{ auth()->user()->canAny(['edit-product', 'delete-product'])? 'true': 'false' }};
    const isShowCheckbox = {{ auth()->user()->can('delete-product') ? 'true' : 'false' }};

    var product_table = $('#product_table').DataTable({
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
            url: "{{ route('product.index') }}",
            data: function(d) {
                d.brand_id = $('#BrandId').val();
            }
        },
        columns: [{
                data: 'id',
                name: 'id',
                visible: false,
                searchable: false
            },
            {
                data: 'checkbox',
                name: 'checkbox',
                orderable: false,
                searchable: false,
                visible: isShowCheckbox
            },
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {
                data: 'name',
                name: 'name',
                searchable: true
            },
            {
                data: 'brand_name',
                name: 'brand_name',
                searchable: false,
                orderable: false
            },
            {
                data: 'unit',
                name: 'unit',
                searchable: true
            },
            // {
            //     data: 'price',
            //     name: 'price',
            //     searchable: false
            // },
            {
                data: 'status',
                name: 'status',
                searchable: false
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                visible: isShowAction
            },
        ]
    });

    /* Brand */
    $('#BrandId').on('change', function() {
        product_table.draw();
    });

    // Custom Search Box
    $('#customSearch').on('keyup', function() {
        product_table.search(this.value).draw();
    });

    // Initialise Select2 inside the modal
    function initModalSelect2() {
        $('#modal_brand_id').select2({
            dropdownParent: $('#adminModal'),
            placeholder: '-- Select Brand --',
            width: '100%'
        });
        $('#modal_unit').select2({
            dropdownParent: $('#adminModal'),
            placeholder: '-- Select Unit --',
            width: '100%'
        });
    }

    // Open modal for adding
    $('#openModal').click(function() {
        $('#adminForm')[0].reset();
        $('#modalTitle').text('Add Product');
        $('#submitBtn').text('Create');
        $('input[name="product_id"]').val('');
        $('input[name="status"][value="1"]').prop('checked', true);
        clearErrors();
        $('#adminModal').modal('show');
    });

    // Initialise Select2 after modal is shown (avoids width issues)
    $('#adminModal').on('shown.bs.modal', function() {
        initModalSelect2();
    });

    // Reset Select2 on modal close
    $('#adminModal').on('hidden.bs.modal', function() {
        if ($('#modal_brand_id').hasClass('select2-hidden-accessible')) {
            $('#modal_brand_id').select2('destroy');
        }
        if ($('#modal_unit').hasClass('select2-hidden-accessible')) {
            $('#modal_unit').select2('destroy');
        }
    });

    // Open modal for editing
    $(document).on('click', '.edit-btn', function() {
        let product_id = $(this).data('id');
        clearErrors();

        $.get('{{ route('product.edit', ':id') }}'.replace(':id', product_id), function(product) {
            $('#modalTitle').text('Edit Product');
            $('#submitBtn').text('Update');
            $('input[name="product_id"]').val(product_id);
            $('input[name="name"]').val(product.name);
            $('input[name="status"][value="' + product.status + '"]').prop('checked', true);
            $('#modal_price').val(product.price);

            // Populate select fields — trigger Select2 change after setting value
            $('#modal_brand_id').val(product.brand_id).trigger('change');
            $('#modal_unit').val(product.unit).trigger('change');

            $('#adminModal').modal('show');
        });
    });

    // Handle form submission (add & edit)
    $('#adminForm').submit(function(e) {
        e.preventDefault();
        clearErrors();

        let product_id = $('input[name="product_id"]').val();
        let url = product_id ?
            '{{ route('product.update', ':id') }}'.replace(':id', product_id) :
            '{{ route('product.store') }}';
        let method = product_id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: $(this).serialize() + '&_method=' + method,
            success: function(response) {
                $('#adminModal').modal('hide');
                product_table.ajax.reload();
                show_success(response.message);
            },
            error: function(response) {
                if (response.responseJSON && response.responseJSON.errors) {
                    display_errors(response.responseJSON.errors);
                }
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

    function clearErrors() {
        $('#adminForm .text-danger.small').text('');
        $('#adminForm').find('.is-invalid').removeClass('is-invalid');
    }

    function display_errors(errors) {
        clearErrors();
        $.each(errors, function(key, value) {
            $('[name="' + key + '"]').addClass('is-invalid');
            $('.' + key + '_error').text(value[0]);
        });
    }

    // Bulk delete — select all
    $('#select-all').change(function() {
        $('.product_checkbox').prop('checked', this.checked);
        toggleBulkBtn();
    });

    $(document).on('change', '.product_checkbox', function() {
        toggleBulkBtn();
    });

    function toggleBulkBtn() {
        let count = $('.product_checkbox:checked').not('#select-all').length;
        count > 0 ? $('#bulk_delete_button').show() : $('#bulk_delete_button').hide();
    }

    $('#bulk_delete_button').click(function() {
        confirmDeletion(function() {
            var selectedIds = $('.product_checkbox:checked').not('#select-all').map(function() {
                return $(this).data('id');
            }).get();

            if (selectedIds.length > 0) {
                $.ajax({
                    url: "{{ route('product.bulkDelete') }}",
                    method: 'POST',
                    data: {
                        ids: selectedIds,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        show_success(response.message);
                        product_table.ajax.reload();
                        $('#bulk_delete_button').hide();
                        $('#select-all').prop('checked', false);
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
