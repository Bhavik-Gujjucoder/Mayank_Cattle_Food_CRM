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
                    <button class="btn btn-danger me-2" id="bulk_delete_button" style="display:none;">
                        <i class="ti ti-trash me-1"></i>Delete Selected
                    </button>
                    @can('add-raw-material-purchas-order')
                        <a href="{{ route('raw-material-order.create') }}" class="btn btn-primary">
                            <i class="ti ti-square-rounded-plus me-2"></i>Purchase Material
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive custom-table">
            <table class="table dataTable no-footer" id="list_table">
                <thead class="thead-light">
                    <tr>
                        <th hidden>ID</th>
                        <th class="no-sort">
                            <label class="checkboxs">
                                <input type="checkbox" id="select-all"><span class="checkmarks"></span>
                            </label>
                        </th>
                        <th class="no-sort">Sr No</th>
                        <th>Purchase ID</th>
                        <th>Raw Material</th>
                        <th>Supplier</th>
                        <th>Invoice No</th>
                        <th>Invoice Date</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Paid</th>
                        <th>Due</th>
                        <th class="no-sort">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- ── Raw Material Details Modal ─────────────────────────────────── --}}
<div class="modal custom-modal fade" id="rawMaterialDetailsModal" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Raw Material Details</h5>
                <div class="d-flex align-items-center mod-toggle">
                    <button type="button" class="btn-close close_poup" data-bs-dismiss="modal">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
            </div>
            <div class="modal-body" id="rawMaterialDetailsBody">
                <div class="text-center py-3"><i class="ti ti-loader ti-spin"></i> Loading…</div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-light" data-bs-dismiss="modal">Close</a>
            </div>
        </div>
    </div>
</div>

{{-- ── Supplier Details Modal ──────────────────────────────────────── --}}
<div class="modal custom-modal fade" id="supplierDetailsModal" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Supplier Details</h5>
                <div class="d-flex align-items-center mod-toggle">
                    <button type="button" class="btn-close close_poup" data-bs-dismiss="modal">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
            </div>
            <div class="modal-body" id="supplierDetailsBody">
                <div class="text-center py-3"><i class="ti ti-loader ti-spin"></i> Loading…</div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-light" data-bs-dismiss="modal">Close</a>
            </div>
        </div>
    </div>
</div>

{{-- ── Payment Modal ───────────────────────────────────────────────── --}}
<div class="modal custom-modal fade" id="paymentModal" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Payment</h5>
                <div class="d-flex align-items-center mod-toggle">
                    <button type="button" class="btn-close close_poup" data-bs-dismiss="modal">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
            </div>
            <form id="paymentForm">
                @csrf
                <input type="hidden" name="purchase_id" id="paymentPurchaseId">
                <div class="modal-body">

                    <div class="mb-3">
                        <label class="col-form-label">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select">
                            <option value="">Select Payment Method</option>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="upi">UPI</option>
                            <option value="cheque">Cheque</option>
                        </select>
                        <span class="payment_method_error text-danger small"></span>
                    </div>

                    <div class="mb-3">
                        <label class="col-form-label">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control">
                        <span class="payment_date_error text-danger small"></span>
                    </div>

                    <div class="mb-3">
                        <label class="col-form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" placeholder="0.00" min="0.01" step="0.01">
                        <span class="amount_error text-danger small"></span>
                    </div>

                    <div class="mb-3">
                        <label class="col-form-label">Transaction ID</label>
                        <input type="text" name="transaction_no" class="form-control" placeholder="Transaction / Ref No">
                    </div>

                    <div class="mb-3">
                        <label class="col-form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Remarks"></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
@section('script')
<script>
    /* ── DataTable ─────────────────────────────────────────────────────── */
    var list_table = $('#list_table').DataTable({
        pageLength: 10,
        deferRender: true,
        processing: true,
        serverSide: true,
        responsive: true,
        dom: 'lrtip',
        order: [[0, 'desc']],
        ajax: "{{ route('raw-material-order.index') }}",
        columns: [
            { data: 'id',                  name: 'id',                  visible: false, searchable: false },
            { data: 'checkbox',            name: 'checkbox',            orderable: false, searchable: false },
            { data: 'DT_RowIndex',         name: 'DT_RowIndex',         orderable: false, searchable: false },
            { data: 'purchase_unique_id',  name: 'purchase_unique_id' },
            { data: 'raw_material_name',   name: 'raw_material_name',   searchable: false, orderable: false },
            { data: 'supplier_name',       name: 'supplier_name',       searchable: false, orderable: false },
            { data: 'invoice_no',          name: 'invoice_no' },
            { data: 'invoice_date',        name: 'invoice_date' },
            { data: 'quantity',            name: 'quantity' },
            { data: 'unit_price',          name: 'unit_price' },
            { data: 'total_price',         name: 'total_price' },
            { data: 'status',              name: 'status',              searchable: false },
            { data: 'paid_amount',         name: 'paid_amount' },
            { data: 'due_amount',          name: 'due_amount' },
            { data: 'action',              name: 'action',              orderable: false, searchable: false },
        ],
        columnDefs: [
            { targets: 0,  createdCell: td => $(td).attr('data-label', 'ID') },
            { targets: 1,  createdCell: td => $(td).attr('data-label', 'Select') },
            { targets: 2,  createdCell: td => $(td).attr('data-label', 'Sr. No.') },
            { targets: 3,  createdCell: td => $(td).attr('data-label', 'Purchase ID') },
            { targets: 4,  createdCell: td => $(td).attr('data-label', 'Raw Material') },
            { targets: 5,  createdCell: td => $(td).attr('data-label', 'Supplier') },
            { targets: 6,  createdCell: td => $(td).attr('data-label', 'Invoice No') },
            { targets: 7,  createdCell: td => $(td).attr('data-label', 'Invoice Date') },
            { targets: 8,  createdCell: td => $(td).attr('data-label', 'Quantity') },
            { targets: 9,  createdCell: td => $(td).attr('data-label', 'Unit Price') },
            { targets: 10, createdCell: td => $(td).attr('data-label', 'Total Price') },
            { targets: 11, createdCell: td => $(td).attr('data-label', 'Status') },
            { targets: 12, createdCell: td => $(td).attr('data-label', 'Paid') },
            { targets: 13, createdCell: td => $(td).attr('data-label', 'Due') },
            { targets: 14, createdCell: td => $(td).attr('data-label', 'Action') },
        ]
    });

    /* Custom search */
    $('#customSearch').on('keyup', function () {
        list_table.search(this.value).draw();
    });

    /* ── Select All / Bulk Delete ──────────────────────────────────────── */
    $('#select-all').on('change', function () {
        $('.raw_material_purchase_checkbox').prop('checked', this.checked);
        toggleBulkDeleteBtn();
    });

    $(document).on('change', '.raw_material_purchase_checkbox', function () {
        toggleBulkDeleteBtn();
        if (!this.checked) $('#select-all').prop('checked', false);
    });

    function toggleBulkDeleteBtn() {
        $('.raw_material_purchase_checkbox:checked').length > 0
            ? $('#bulk_delete_button').show()
            : $('#bulk_delete_button').hide();
    }

    $('#bulk_delete_button').on('click', function () {
        let ids = $('.raw_material_purchase_checkbox:checked').map(function () { return $(this).data('id'); }).get();

        Swal.fire({
            title: 'Are you sure?',
            text: ids.length + ' order(s) will be deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete!',
            cancelButtonText: 'Cancel',
            customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-secondary' }
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route('raw-material-order.index') }}',
                    method: 'DELETE',
                    data: { ids: ids, _token: '{{ csrf_token() }}' },
                    success: function (response) {
                        show_success(response.message ?? 'Deleted successfully.');
                        list_table.ajax.reload();
                        $('#bulk_delete_button').hide();
                        $('#select-all').prop('checked', false);
                    },
                    error: function () { show_error('An error occurred while deleting.'); }
                });
            }
        });
    });

    /* ── Delete single ─────────────────────────────────────────────────── */
    $(document).on('click', '.delete-purchase-btn', function (e) {
        e.preventDefault();
        let id   = $(this).data('id');
        let form = $('#delete-purchase-form-' + id);

        Swal.fire({
            title: 'Are you sure?',
            text: 'This purchase order will be deleted permanently.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-secondary' }
        }).then(result => { if (result.isConfirmed) form.submit(); });
    });

    /* ── Payment Modal ─────────────────────────────────────────────────── */
    $(document).on('click', '.payment-modal', function () {
        let purchase_id = $(this).data('id');
        $('#paymentPurchaseId').val(purchase_id);
        $('#paymentModal').modal('show');
    });

    /* ── Raw Material Details Modal ────────────────────────────────────── */
    $(document).on('click', '.open-raw-material-details-modal', function () {
        $('#rawMaterialDetailsModal').modal('show');
    });

    /* ── Supplier Details Modal ────────────────────────────────────────── */
    $(document).on('click', '.open-supplier-details-modal', function () {
        $('#supplierDetailsModal').modal('show');
    });
</script>
@endsection
