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

                    <a href="javascript:void(0);" class="btn btn-primary" id="openRawMaterialModal">
                        <i class="ti ti-square-rounded-plus me-2"></i>Add Inventory
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive custom-table">
            <table class="table dataTable no-footer" id="raw_material_table">
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
                        <th>Unit</th>
                        <th>Total Stock</th>
                        <th>Available Stock</th>
                        <th>Used Stock</th>
                        <th>Last Purchase Price</th>
                        <th>Average Price</th>
                        <th>Status</th>
                        <th class="no-sort">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- ── Add / Edit Modal ──────────────────────────────────────────────── --}}
<div class="modal custom-modal fade" id="rawMaterialModal" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rawMaterialModalTitle">Add Inventory</h5>
                <div class="d-flex align-items-center mod-toggle">
                    <button type="button" class="btn-close close_poup" data-bs-dismiss="modal">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
            </div>

            <form id="rawMaterialForm" >
                @csrf
                <input type="hidden" name="raw_material_id">

                <div class="modal-body">

                    {{-- Name --}}
                    <div class="mb-3">
                        <label class="col-form-label">Name <span class="text-dangers">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Inventory name" maxlength="255">
                        <span class="name_error text-danger small"></span>
                    </div>

                    {{-- Unit --}}
                    <div class="mb-3">
                        <label class="col-form-label">Unit <span class="text-dangers">*</span></label>
                        <input type="text" name="unit" class="form-control" placeholder="e.g. KG, L, Piece" maxlength="50">
                        <span class="unit_error text-danger small"></span>
                    </div>

                    {{-- Last Purchase Price --}}
                    <div class="mb-3">
                        <label class="col-form-label">Last Purchase Price (₹)</label>
                        <input type="number" name="last_purchase_price" class="form-control"
                            placeholder="0.00" min="0" step="0.01">
                        <span class="last_purchase_price_error text-danger small"></span>
                    </div>

                    {{-- Status --}}
                    <div class="mb-3">
                        <label class="col-form-label">Status <span class="text-danger">*</span></label>
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <input type="radio" class="status-radio" id="rm_active" name="status" value="1" checked>
                                <label for="rm_active">Active</label>
                            </div>
                            <div>
                                <input type="radio" class="status-radio" id="rm_inactive" name="status" value="0">
                                <label for="rm_inactive">Inactive</label>
                            </div>
                        </div>
                        <span class="status_error text-danger small"></span>
                    </div>

                </div>

                <div class="modal-footer">
                    <div class="d-flex align-items-center justify-content-end m-0">
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="rawMaterialSubmitBtn">Save</button>
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
    var raw_material_table = $('#raw_material_table').DataTable({
        pageLength: 10,
        deferRender: true,
        processing: true,
        serverSide: true,
        responsive: true,
        dom: 'lrtip',
        order: [[0, 'desc']],
        ajax: "{{ route('raw-material.index') }}",
        columns: [
            { data: 'id',               name: 'id',               visible: false, searchable: false },
            { data: 'checkbox',         name: 'checkbox',         orderable: false, searchable: false },
            { data: 'DT_RowIndex',      name: 'DT_RowIndex',      orderable: false, searchable: false },
            { data: 'name',             name: 'name' },
            { data: 'unit',             name: 'unit' },
            { data: 'total_stock',      name: 'total_stock' },
            { data: 'available_stock',  name: 'available_stock' },
            { data: 'used_stock',       name: 'used_stock' },
            { data: 'last_purchase_price', name: 'last_purchase_price' },
            { data: 'average_price',    name: 'average_price' },
            { data: 'status',           name: 'status' },
            { data: 'action',           name: 'action', orderable: false, searchable: false },
        ],
        columnDefs: [
            { targets: 0,  createdCell: td => $(td).attr('data-label', 'ID') },
            { targets: 1,  createdCell: td => $(td).attr('data-label', 'Select') },
            { targets: 2,  createdCell: td => $(td).attr('data-label', 'Sr. No.') },
            { targets: 3,  createdCell: td => $(td).attr('data-label', 'Name') },
            { targets: 4,  createdCell: td => $(td).attr('data-label', 'Unit') },
            { targets: 5,  createdCell: td => $(td).attr('data-label', 'Total Stock') },
            { targets: 6,  createdCell: td => $(td).attr('data-label', 'Available Stock') },
            { targets: 7,  createdCell: td => $(td).attr('data-label', 'Used Stock') },
            { targets: 8,  createdCell: td => $(td).attr('data-label', 'Last Purchase Price') },
            { targets: 9,  createdCell: td => $(td).attr('data-label', 'Average Price') },
            { targets: 10, createdCell: td => $(td).attr('data-label', 'Status') },
            { targets: 11, createdCell: td => $(td).attr('data-label', 'Action') },
        ]
    });

    /* Custom search */
    $('#customSearch').on('keyup', function () {
        raw_material_table.search(this.value).draw();
    });

    /* ── Open modal for Add ────────────────────────────────────────────── */
    $('#openRawMaterialModal').on('click', function () {
        $('#rawMaterialForm')[0].reset();
        $('input[name="raw_material_id"]').val('');
        $('#rawMaterialModalTitle').text('Add Inventory');
        $('#rawMaterialSubmitBtn').text('Save');
        clearRawMaterialErrors();
        $('input[name="status"][value="1"]').prop('checked', true);
        $('#rawMaterialModal').modal('show');
    });

    /* ── Open modal for Edit ───────────────────────────────────────────── */
    $(document).on('click', '.edit-raw-material-btn', function () {
        let id = $(this).data('id');
        clearRawMaterialErrors();

        $.get('{{ route('raw-material.edit', ':id') }}'.replace(':id', id), function (data) {
            $('#rawMaterialModalTitle').text('Edit Inventory');
            $('#rawMaterialSubmitBtn').text('Update');
            $('input[name="raw_material_id"]').val(data.id);
            $('input[name="name"]').val(data.name);
            $('input[name="unit"]').val(data.unit);
            $('input[name="last_purchase_price"]').val(data.last_purchase_price);
            $('input[name="status"][value="' + data.status + '"]').prop('checked', true);
            $('#rawMaterialModal').modal('show');
        });
    });

    /* ── Form Submit (Add & Edit) ──────────────────────────────────────── */
    $('#rawMaterialForm').on('submit', function (e) {
        e.preventDefault();
        clearRawMaterialErrors();

        let id     = $('input[name="raw_material_id"]').val();
        let url    = id ? '{{ route('raw-material.update', ':id') }}'.replace(':id', id)
                       : '{{ route('raw-material.store') }}';
        let method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: $(this).serialize() + '&_method=' + method,
            success: function (response) {
                $('#rawMaterialModal').modal('hide');
                raw_material_table.ajax.reload();
                show_success(response.message);
            },
            error: function (response) {
                displayRawMaterialErrors(response.responseJSON.errors);
            }
        });
    });

    /* ── Delete ────────────────────────────────────────────────────────── */
    $(document).on('click', '.delete-raw-material-btn', function (e) {
        e.preventDefault();
        let id   = $(this).data('id');
        let form = $('#delete-raw-material-form-' + id);

        Swal.fire({
            title: 'Are you sure?',
            text: 'This Inventory will be deleted permanently.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-secondary',
            }
        }).then(result => { if (result.isConfirmed) form.submit(); });
    });

    /* ── Bulk Delete ───────────────────────────────────────────────────── */
    $('#select-all').on('change', function () {
        $('.raw_material_checkbox').prop('checked', this.checked);
        toggleBulkDeleteBtn();
    });

    $(document).on('change', '.raw_material_checkbox', function () {
        toggleBulkDeleteBtn();
        if (!this.checked) $('#select-all').prop('checked', false);
    });

    function toggleBulkDeleteBtn() {
        let count = $('.raw_material_checkbox:checked').length;
        count > 0 ? $('#bulk_delete_button').show() : $('#bulk_delete_button').hide();
    }

    $('#bulk_delete_button').on('click', function () {
        let ids = $('.raw_material_checkbox:checked').map(function () { return $(this).data('id'); }).get();

        Swal.fire({
            title: 'Are you sure?',
            text: 'You want to remove this Inventory',
            // text: ids.length + ' Inventory(s) will be deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete!',
            cancelButtonText: 'Cancel',
            customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-secondary' }
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route('raw-material.bulkDelete') }}',
                    method: 'POST',
                    data: { ids: ids, _token: '{{ csrf_token() }}' },
                    success: function (response) {
                        show_success(response.message);
                        raw_material_table.ajax.reload();
                        $('#bulk_delete_button').hide();
                        $('#select-all').prop('checked', false);
                    },
                    error: function () { show_error('An error occurred while deleting.'); }
                });
            }
        });
    });

    /* ── Helpers ───────────────────────────────────────────────────────── */
    function clearRawMaterialErrors() {
        $('#rawMaterialForm .text-danger').text('');
        $('#rawMaterialForm .is-invalid').removeClass('is-invalid');
    }

    function displayRawMaterialErrors(errors) {
        $.each(errors, function (key, value) {
            $('input[name="' + key + '"], select[name="' + key + '"], textarea[name="' + key + '"]').addClass('is-invalid');
            $('.' + key + '_error').text(value[0]);
        });
    }
</script>
@endsection
