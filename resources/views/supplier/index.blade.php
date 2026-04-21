@extends('layouts.main')
@section('title') {{ $page_title }} @endsection
@section('content')

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

                    <a href="javascript:void(0);" class="btn btn-primary" id="openSupplierModal">
                        <i class="ti ti-square-rounded-plus me-2"></i>Add Supplier
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive custom-table">
            <table class="table dataTable no-footer" id="supplier_table">
                 <button class="btn btn-danger me-2" id="bulk_delete_button" style="display:none;">
                        <i class="ti ti-trash me-1"></i>Delete Selected
                    </button>
                <thead class="thead-light">
                    <tr>
                        <th hidden>ID</th>
                        <th class="no-sort">
                            <label class="checkboxs">
                                <input type="checkbox" id="select-all"><span class="checkmarks"></span>
                            </label>
                        </th>
                        <th class="no-sort">Sr No</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Opening Balance</th>
                        <th>Status</th>
                        <th class="no-sort">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- ── Add / Edit Modal ──────────────────────────────────────────────── --}}
<div class="modal custom-modal fade" id="supplierModal" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="supplierModalTitle">Add Supplier</h5>
                <div class="d-flex align-items-center mod-toggle">
                    <button type="button" class="btn-close close_poup" data-bs-dismiss="modal">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
            </div>

            <form id="supplierForm">
                @csrf
                <input type="hidden" name="supplier_id">

                <div class="modal-body">
                    <div class="row">

                        {{-- Name --}}
                        <div class="col-md-6 mb-3">
                            <label class="col-form-label">Name <span class="text-dangers">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Supplier name" maxlength="255">
                            <span class="name_error text-danger small"></span>
                        </div>

                        {{-- Mobile --}}
                        <div class="col-md-6 mb-3">
                            <label class="col-form-label">Mobile <span class="text-dangers">*</span></label>
                            <input type="text" name="mobile" class="form-control" placeholder="Mobile number" maxlength="10"
                                oninput="this.value = this.value.replace(/[^0-9+\-\s]/g,'')">
                            <span class="mobile_error text-danger small"></span>
                        </div>

                        {{-- Email --}}
                        <div class="col-md-6 mb-3">
                            <label class="col-form-label">Email <span class="text-dangers">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="Email address" maxlength="255">
                            <span class="email_error text-danger small"></span>
                        </div>

                        {{-- Opening Balance --}}
                        <div class="col-md-6 mb-3">
                            <label class="col-form-label">Opening Balance (₹)</label>
                            <input type="number" name="opening_balance" class="form-control"
                                placeholder="0.00" min="0" step="0.01">
                            <span class="opening_balance_error text-danger small"></span>
                        </div>

                        {{-- Address --}}
                        <div class="col-md-12 mb-3">
                            <label class="col-form-label">Address <span class="text-dangers">*</span></label>
                            <textarea name="address" class="form-control" rows="3" placeholder="Supplier address"></textarea>
                            <span class="address_error text-danger small"></span>
                        </div>

                        {{-- Status --}}
                        <div class="col-md-12 mb-3">
                            <label class="col-form-label">Status <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <input type="radio" class="status-radio" id="sup_active" name="status" value="1" checked>
                                    <label for="sup_active">Active</label>
                                </div>
                                <div>
                                    <input type="radio" class="status-radio" id="sup_inactive" name="status" value="0">
                                    <label for="sup_inactive">Inactive</label>
                                </div>
                            </div>
                            <span class="status_error text-danger small"></span>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <div class="d-flex align-items-center justify-content-end m-0">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="supplierSubmitBtn">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
@section('script')
<script>
    /* ── DataTable ─────────────────────────────────────────────────────── */
    var supplier_table = $('#supplier_table').DataTable({
        pageLength: 10,
        deferRender: true,
        processing: true,
        serverSide: true,
        responsive: true,
        dom: 'lrtip',
        order: [[0, 'desc']],
        ajax: "{{ route('supplier.index') }}",
        columns: [
            { data: 'id',               name: 'id',               visible: false, searchable: false },
            { data: 'checkbox',         name: 'checkbox',         orderable: false, searchable: false },
            { data: 'DT_RowIndex',      name: 'DT_RowIndex',      orderable: false, searchable: false },
            { data: 'name',             name: 'name' },
            { data: 'mobile',           name: 'mobile' },
            { data: 'email',            name: 'email' },
            { data: 'address',          name: 'address', orderable: false },
            { data: 'opening_balance',  name: 'opening_balance' },
            { data: 'status',           name: 'status' },
            { data: 'action',           name: 'action', orderable: false, searchable: false },
        ],
        columnDefs: [
            { targets: 0, createdCell: td => $(td).attr('data-label', 'ID') },
            { targets: 1, createdCell: td => $(td).attr('data-label', 'Select') },
            { targets: 2, createdCell: td => $(td).attr('data-label', 'Sr. No.') },
            { targets: 3, createdCell: td => $(td).attr('data-label', 'Name') },
            { targets: 4, createdCell: td => $(td).attr('data-label', 'Mobile') },
            { targets: 5, createdCell: td => $(td).attr('data-label', 'Email') },
            { targets: 6, createdCell: td => $(td).attr('data-label', 'Address') },
            { targets: 7, createdCell: td => $(td).attr('data-label', 'Opening Balance') },
            { targets: 8, createdCell: td => $(td).attr('data-label', 'Status') },
            { targets: 9, createdCell: td => $(td).attr('data-label', 'Action') },
        ]
    });

    /* Custom search */
    $('#customSearch').on('keyup', function () {
        supplier_table.search(this.value).draw();
    });

    /* ── Open modal for Add ────────────────────────────────────────────── */
    $('#openSupplierModal').on('click', function () {
        $('#supplierForm')[0].reset();
        $('input[name="supplier_id"]').val('');
        $('#supplierModalTitle').text('Add Supplier');
        $('#supplierSubmitBtn').text('Save');
        clearSupplierErrors();
        $('input[name="status"][value="1"]').prop('checked', true);
        $('#supplierModal').modal('show');
    });

    /* ── Open modal for Edit ───────────────────────────────────────────── */
    $(document).on('click', '.edit-supplier-btn', function () {
        let id = $(this).data('id');
        clearSupplierErrors();

        $.get('{{ route('supplier.edit', ':id') }}'.replace(':id', id), function (data) {
            $('#supplierModalTitle').text('Edit Supplier');
            $('#supplierSubmitBtn').text('Update');
            $('input[name="supplier_id"]').val(data.id);
            $('input[name="name"]').val(data.name);
            $('input[name="mobile"]').val(data.mobile);
            $('input[name="email"]').val(data.email);
            $('textarea[name="address"]').val(data.address);
            $('input[name="opening_balance"]').val(data.opening_balance);
            $('input[name="status"][value="' + data.status + '"]').prop('checked', true);
            $('#supplierModal').modal('show');
        });
    });

    /* ── Form Submit (Add & Edit) ──────────────────────────────────────── */
    $('#supplierForm').on('submit', function (e) {
        e.preventDefault();
        clearSupplierErrors();

        let id     = $('input[name="supplier_id"]').val();
        let url    = id ? '{{ route('supplier.update', ':id') }}'.replace(':id', id)
                       : '{{ route('supplier.store') }}';
        let method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: $(this).serialize() + '&_method=' + method,
            success: function (response) {
                $('#supplierModal').modal('hide');
                supplier_table.ajax.reload();
                show_success(response.message);
            },
            error: function (response) {
                displaySupplierErrors(response.responseJSON.errors);
            }
        });
    });

    /* ── Delete ────────────────────────────────────────────────────────── */
    $(document).on('click', '.delete-supplier-btn', function (e) {
        e.preventDefault();
        let id   = $(this).data('id');
        let form = $('#delete-supplier-form-' + id);

        Swal.fire({
            title: 'Are you sure?',
            text: 'This supplier will be deleted permanently.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-secondary' }
        }).then(result => { if (result.isConfirmed) form.submit(); });
    });

    /* ── Bulk Delete ───────────────────────────────────────────────────── */
    $('#select-all').on('change', function () {
        $('.supplier_checkbox').prop('checked', this.checked);
        toggleBulkDeleteBtn();
    });

    $(document).on('change', '.supplier_checkbox', function () {
        toggleBulkDeleteBtn();
        if (!this.checked) $('#select-all').prop('checked', false);
    });

    function toggleBulkDeleteBtn() {
        let count = $('.supplier_checkbox:checked').length;
        count > 0 ? $('#bulk_delete_button').show() : $('#bulk_delete_button').hide();
    }

    $('#bulk_delete_button').on('click', function () {
        let ids = $('.supplier_checkbox:checked').map(function () { return $(this).data('id'); }).get();

        Swal.fire({
            title: 'Are you sure?',
            text: 'You want to remove this supplier.',
            // text: ids.length + ' supplier(s) will be deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete!',
            cancelButtonText: 'Cancel',
            customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-secondary' }
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route('supplier.bulkDelete') }}',
                    method: 'POST',
                    data: { ids: ids, _token: '{{ csrf_token() }}' },
                    success: function (response) {
                        show_success(response.message);
                        supplier_table.ajax.reload();
                        $('#bulk_delete_button').hide();
                        $('#select-all').prop('checked', false);
                    },
                    error: function () { show_error('An error occurred while deleting.'); }
                });
            }
        });
    });

    /* ── Helpers ───────────────────────────────────────────────────────── */
    function clearSupplierErrors() {
        $('#supplierForm .text-danger').text('');
        $('#supplierForm .is-invalid').removeClass('is-invalid');
    }

    function displaySupplierErrors(errors) {
        $.each(errors, function (key, value) {
            $('input[name="' + key + '"], select[name="' + key + '"], textarea[name="' + key + '"]').addClass('is-invalid');
            $('.' + key + '_error').text(value[0]);
        });
    }
</script>
@endsection
