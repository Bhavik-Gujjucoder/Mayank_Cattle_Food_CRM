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
                    <input type="text" class="form-control" placeholder="Search">
                </div>
            </div>
            <div class="col-sm-8">
                <div class="d-flex align-items-center flex-wrap row-gap-2 justify-content-sm-end">
                    <a href="{{ route('raw-material-order.create') }}" class="btn btn-primary"><i class="ti ti-square-rounded-plus me-2"></i>Purchase Material</a>
                </div>
            </div>
        </div>
        <!-- /Search -->
    </div>
    <div class="card-body">
        <!-- order management List -->
        <div class="table-responsive custom-table">

            <div id="order_management_wrapper" class="dataTables_wrapper table-responsive">
                <!--<div class="dataTables_length" id="order_management_length">
                    <label>
                        Show
                        <select name="order_management_length" class="form-select form-select-sm">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        entries
                    </label>
                </div>-->

                <table class="table dataTable" id="list_table1">
                    <thead class="thead-light">
                        <tr>
                            <th class="no-sort sorting_disabled">
                                <label class="checkboxs">
                                    <input type="checkbox" id="select-all" class="order_checkbox"><span
                                        class="checkmarks"></span></label>
                            </th>
                            <th class="no-sort" class="sorting">SR.No</th>
                            <th scope="col" class="sorting">Raw Material</th>
                            <th scope="col" class="sorting">Supplier</th>
                            <th scope="col" class="sorting">Invoice No</th>
                            <th scope="col" class="sorting">Invoice Date</th>
                            <th scope="col" class="sorting">Quantity</th>
                            <th scope="col" class="sorting">Unit Price</th>
                            <th scope="col" class="sorting">Total Price</th>
                            <th scope="col" class="sorting">Status</th>
                            <th scope="col" class="sorting">Paid Amount</th>
                            <th scope="col" class="sorting">Due Amount</th>
                            <th class="sorting_disabled" aria-label="Action">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="odd">
                            <td data-label="Select"><label class="checkboxs">
                                    <input type="checkbox" class="checkbox-item order_checkbox" data-id="99">
                                    <span class="checkmarks"></span>
                                </label>
                            </td>
                            <td data-label="Sr no">1</td>
                            <td data-label="Raw Material">
                                <a href="#" class="show-btn open-raw-material-details-model" data-id="1">
                                    <i class="ti ti-eye #1ecbe2"></i> Maize (Corn)
                                </a>
                            </td>
                            <td data-label="Supplier"><a href="#" class="show-btn open-supplier-details-model"
                                    data-id="99">
                                    <i class="ti ti-eye #1ecbe2"></i> Cargill India</a>
                            </td>
                            <td data-label="City">CF/RAW/2026/0157</td>
                            <td data-label="Order Date">26 Jan 2026</td>
                            <td data-label="Contact Number">10000</td>
                            <td data-label="Salesman">₹40</td>
                            <td data-label="Total">₹4,00,000</td>
                            <td data-label="Action">
                                <div class="dropdown table-action order_drpdown">
                                    <span class="badge badge-pill badge-status bg-secondary">Pending</span>
                                    <a href="#" class="action-icon" data-bs-toggle="dropdown"
                                        aria-expanded="false"><i class="fa fa-pencil"></i></a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a href="javascript:void(0)"
                                            class="dropdown-item change-status" data-id="99" data-status="2">
                                            <span class="badge bg-success">Received</span>
                                        </a>
                                        <a href="javascript:void(0)"
                                            class="dropdown-item change-status" data-id="99" data-status="2">
                                            <span class="badge bg-danger">Cancelled</span>
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Total"></td>
                            <td data-label="Total">₹4,00,000</td>
                            <td>
                                <div class="dropdown table-action">
                                    <a href="#" class="action-icon " data-bs-toggle="dropdown"
                                        aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a href="{{ route('raw-material-order.edit', 1) }}" class="dropdown-item"><i
                                                class="ti ti-edit text-warning"></i> Edit</a>
                                        <a href="javascript:void(0)" class="dropdown-item deleteOrder"
                                            data-id="99"> <i class="ti ti-trash text-danger"></i> Delete</a>
                                        <form action="#" method="post" class="delete-form">
                                            <input type="hidden"><input type="hidden" name="_method"
                                                value="DELETE">
                                        </form>
                                        <a href="javascript:void(0);" class="dropdown-item payment-modal" data-id="99"><i
                                                class="ti ti-coin-rupee text-primary"></i> Payment</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="even">
                            <td data-label="Select"><label class="checkboxs">
                                    <input type="checkbox" class="checkbox-item order_checkbox">
                                    <span class="checkmarks"></span>
                                </label>
                            </td>
                            <td data-label="Sr no">2</td>
                            <td data-label="Raw Material"><a href="#" class="show-btn open-raw-material-details-model"
                                    data-id="99">
                                    <i class="ti ti-eye #1ecbe2"></i> Soybean meal</a>
                            </td>
                            <td data-label="Order ID"><a href="" class="show-btn open-supplier-details-model">
                                    <i class="ti ti-eye #1ecbe2"></i> Vishal Traders</a>
                            </td>
                            <td data-label="City">CF/RAW/2026/0158</td>
                            <td data-label="Order Date">26 Jan 2026</td>
                            <td data-label="Contact Number">15000</td>
                            <td data-label="Salesman">₹45</td>
                            <td data-label="Total">₹6,75,000</td>
                            <td data-label="Action">
                                <div class="dropdown table-action order_drpdown">
                                    <span class="badge badge-pill badge-status bg-secondary">Pending</span>
                                    <a href="#" class="action-icon" data-bs-toggle="dropdown"
                                        aria-expanded="false"><i class="fa fa-pencil"></i></a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a href="javascript:void(0)"
                                            class="dropdown-item change-status">
                                            <span class="badge bg-success">Complete</span>
                                        </a>
                                        <a href="javascript:void(0)"
                                            class="dropdown-item change-status" data-id="99" data-status="2">
                                            <span class="badge bg-danger">Cancelled</span>
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Total"></td>
                            <td data-label="Total">₹6,75,000</td>
                            <td>
                                <div class="dropdown table-action">
                                    <a href="#" class="action-icon " data-bs-toggle="dropdown"
                                        aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a href="{{ route('raw-material-order.edit', 1) }}" class="dropdown-item"><i
                                                class="ti ti-edit text-warning"></i> Edit</a>
                                        <a href="javascript:void(0)" class="dropdown-item deleteOrder"
                                            data-id="98"> <i class="ti ti-trash text-danger"></i> Delete</a>
                                        <form action="" method="post" class="delete-form"><input
                                                type="hidden" name="_token"><input type="hidden" value="DELETE">
                                        </form>
                                        <a href="javascript:void(0);" class="dropdown-item payment-modal" data-id="99"><i
                                                class="ti ti-coin-rupee text-primary"></i> Payment</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr class="odd">
                            <td data-label="Select"><label class="checkboxs">
                                    <input type="checkbox" class="checkbox-item order_checkbox">
                                    <span class="checkmarks"></span>
                                </label>
                            </td>
                            <td data-label="Sr no">3</td>
                            <td data-label="Raw Material"><a href="#" class="show-btn open-raw-material-details-model"
                                    data-id="99">
                                    <i class="ti ti-eye #1ecbe2"></i> Cottonseed cake</a>
                            </td>
                            <td data-label="Order ID"><a href="" class="show-btn open-supplier-details-model">
                                    <i class="ti ti-eye #1ecbe2"></i> RK International</a>
                            </td>
                            <td data-label="City">CF/RAW/2026/0159</td>
                            <td data-label="Order Date">24 Jan 2026</td>
                            <td data-label="Contact Number">23000</td>
                            <td data-label="Salesman">₹38</td>
                            <td data-label="Total">₹8,74,000</td>
                            <td data-label="Action">
                                <div class="dropdown table-action order_drpdown">
                                    <span class="badge badge-pill badge-status bg-secondary">Pending</span>
                                    <a href="#" class="action-icon" data-bs-toggle="dropdown"
                                        aria-expanded="false"><i class="fa fa-pencil"></i></a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a href="javascript:void(0)"
                                            class="dropdown-item change-status" data-id="97" data-status="2">
                                            <span class="badge bg-success">Complete</span>
                                        </a>
                                        <a href="javascript:void(0)"
                                            class="dropdown-item change-status" data-id="99" data-status="2">
                                            <span class="badge bg-danger">Cancelled</span>
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Total"></td>
                            <td data-label="Total">₹8,74,000</td>
                            <td>
                                <div class="dropdown table-action">
                                    <a href="#" class="action-icon " data-bs-toggle="dropdown"
                                        aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a href="{{ route('raw-material-order.edit', 1) }}" class="dropdown-item"><i
                                                class="ti ti-edit text-warning"></i> Edit</a>
                                        <a href="javascript:void(0)" class="dropdown-item deleteOrder"> <i
                                                class="ti ti-trash text-danger"></i> Delete</a>
                                        <form class="delete-form"><input type="hidden"><input type="hidden"
                                                name="_method" value="DELETE">
                                        </form>
                                        <a href="javascript:void(0);" class="dropdown-item payment-modal" data-id="99"><i
                                                class="ti ti-coin-rupee text-primary"></i> Payment</a>
                                    </div>
                                </div>
                            </td>
                        </tr>


                    </tbody>
                </table>
                <div class="dataTables_info" id="order_management_info" role="status" aria-live="polite">Showing 1
                    to 10 of 72 entries</div>
                <div class="dataTables_paginate paging_simple_numbers" id="order_management_paginate">
                    <ul class="pagination">
                        <li class="paginate_button page-item previous disabled" id="order_management_previous"><a
                                aria-controls="order_management" aria-disabled="true" role="link"
                                data-dt-idx="previous" tabindex="-1" class="page-link">Previous</a></li>
                        <li class="paginate_button page-item active"><a href="#" class="page-link">1</a></li>
                        <li class="paginate_button page-item "><a href="#" class="page-link">2</a></li>
                        <li class="paginate_button page-item "><a href="#" class="page-link">3</a></li>
                        <li class="paginate_button page-item "><a href="#" class="page-link">4</a></li>
                        <li class="paginate_button page-item "><a href="#" class="page-link">5</a></li>
                        <li class="paginate_button page-item disabled"><a aria-controls="order_management"
                                aria-disabled="true" role="link" data-dt-idx="ellipsis" tabindex="-1"
                                class="page-link">…</a></li>
                        <li class="paginate_button page-item "><a href="#" aria-controls="order_management"
                                role="link" data-dt-idx="7" tabindex="0" class="page-link">8</a></li>
                        <li class="paginate_button page-item next" id="order_management_next"><a href="#"
                                aria-controls="order_management" role="link" data-dt-idx="next" tabindex="0"
                                class="page-link">Next</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="datatable-length"></div>
            </div>
            <div class="col-md-6">
                <div class="datatable-paginate"></div>
            </div>
        </div>
    </div>
</div>
<!--Raw Material Details Modal -->
<div class="modal custom-modal fade" id="rawMaterialDetailsModal" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Raw Material Details</h5>
                <div class="d-flex align-items-center mod-toggle">
                    {{-- <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button> --}}
                    <button type="button" class="btn-close close_poup" data-bs-dismiss="modal"><i
                            class="ti ti-x"></i></button>
                </div>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="col-form-label">Material Details</label>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Raw Material</th>
                                <th>Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Maize (Corn)</td>
                                <td>kg</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mb-3">
                    <label class="col-form-label">Stock Details</label>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Total Stock</th>
                                <th>Available Stock</th>
                                <th>Used Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>10000 kg</td>
                                <td>5000 kg</td>
                                <td>5000 kg</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mb-3">
                    <label class="col-form-label">Price Details</label>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Last Purchase Price</th>
                                <th>Average Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>₹ 40.00</td>
                                <td>₹ 40.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <div class="d-flex align-items-center justify-content-end m-0">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</div>
<!--Supplier Details Modal -->
<div class="modal custom-modal fade" id="supplierDetailsModal" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Supplier Details</h5>
                <div class="d-flex align-items-center mod-toggle">
                    <button type="button" class="btn-close close_poup" data-bs-dismiss="modal"><i
                            class="ti ti-x"></i></button>
                </div>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="col-form-label">Supplier Details</label>
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th>Supplier Name</th>
                                <td>Cargill India</td>
                            </tr>
                            <tr>
                                <th>Supplier Address</th>
                                <td>123, Main Street, Anytown, USA</td>
                            </tr>
                            <tr>
                                <th>Supplier Contact Number</th>
                                <td>1234567890</td>
                            </tr>
                            <tr>
                                <th>Supplier Email</th>
                                <td>info@cargillindia.com</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <div class="d-flex align-items-center justify-content-end m-0">
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</div>
<!--Payment Modal -->
<div class="modal custom-modal fade" id="paymentModal" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Manage Payment for Purchase</h5>
                <div class="d-flex align-items-center mod-toggle">
                    {{-- <button class="btn-close custom-btn-close border p-1 me-0 text-dark" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button> --}}
                    <button type="button" class="btn-close close_poup" data-bs-dismiss="modal"><i
                            class="ti ti-x"></i></button>
                </div>
            </div>
            <form id="adminForm">
                @csrf
                <input type="hidden" name="state_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="col-form-label">Purchase Details</label>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Raw Material</th>
                                    <th>Supplier</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Maize (Corn)</td>
                                    <td>Cargill India</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-3">
                        <label class="col-form-label">Invoice Details</label>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Invoice No</th>
                                    <th>Invoice Date</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>CF/RAW/2026/0157</td>
                                    <td>26 Jan 2026</td>
                                    <td>10000</td>
                                    <td>₹40</td>
                                    <td>₹4,00,000</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-3">
                        <label class="col-form-label">Payment Details</label>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Payment Method</th>
                                    <th>Due Amount</th>
                                    <th>Paid Amount</th>
                                    <th>Payment Date</th>
                                    <th>Transaction ID</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Cash/Bank/UPI</td>
                                    <td>₹4,00,000</td>
                                    <td>₹0</td>
                                    <td>26 Jan 2026</td>
                                    <td>1234567890</td>
                                    <td>Payment for Invoice CF/RAW/2026/0157</td>
                                </tr>
                            </tbody>
                            <tbody>
                                <tr>
                                    <td>Cash/Bank/UPI</td>
                                    <td>₹2,00,000</td>
                                    <td>₹2,00,000</td>
                                    <td>26 Jan 2026</td>
                                    <td>1234567890</td>
                                    <td>Payment for Invoice CF/RAW/2026/0158</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-3">
                        <label class="col-form-label">Payment Method *</label>
                        <select name="payment_method" id="raw_material_idpayment_method"
                            class="form-select search-select @error('payment_method') is-invalid @enderror">
                            <option value="">Select Payment Method</option>
                            <option value="">Cash</option>
                            <option value="">Bank</option>
                            <option value="">UPI</option>
                        </select>
                        <span class="payment_method_error"></span>
                    </div>
                    <div class="mb-3">
                        <label class="col-form-label">Payment Date *</label>
                        <input type="date" name="payment_date" id="payment_date" value="" class="form-control @error('payment_date') is-invalid @enderror"
                            placeholder="Payment Date">
                        <span class="payment_date_error"></span>
                    </div>
                    <div class="mb-3">
                        <label class="col-form-label">Pay *</label>
                        <input type="number" name="amount" id="amount" value="" class="form-control @error('amount') is-invalid @enderror"
                            placeholder="Amount">
                        <span class="amount_error"></span>
                    </div>
                    <div class="mb-3">
                        <label class="col-form-label">Transaction ID *</label>
                        <input type="text" name="transaction_no" id="transaction_no" value="" class="form-control @error('transaction_no') is-invalid @enderror"
                            placeholder="Transaction No">
                        <span class="transaction_no_error"></span>
                    </div>
                    <div class="mb-3">
                        <label class="col-form-label">Remarks</label>
                        <textarea name="remarks" id="remarks"
                            class="form-control @error('remarks') is-invalid @enderror" placeholder="Enter Remarks" rows="2"
                            >{{ old('remarks') }}</textarea>
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
    var list_table = $('#list_table').DataTable({
        "pageLength": 10,
        deferRender: true, // Prevents unnecessary DOM rendering
        processing: true,
        serverSide: true,
        responsive: true,
        dom: 'lrtip',
        order: [
            [0, 'desc']
        ],
        ajax: "{{ route('raw-material-order.index') }}",
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
                searchable: false
            },
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            }, {
                data: 'raw_material_name',
                name: 'raw_material_name',
                searchable: true
            }, {
                data: 'supplier_name',
                name: 'supplier_name',
                searchable: true
            }, {
                data: 'invoice_no',
                name: 'invoice_no',
                searchable: true
            }, {
                data: 'invoice_date',
                name: 'invoice_date',
                searchable: true
            }, {
                data: 'quantity',
                name: 'quantity',
                searchable: true
            }, {
                data: 'unit_price',
                name: 'unit_price',
                searchable: true
            }, {
                data: 'total_price',
                name: 'total_price',
                searchable: true
            }, {
                data: 'status',
                name: 'status',
                searchable: false
            },
            {
                data: 'paid_amount',
                name: 'paid_amount',
                searchable: true
            }, 
            {
                data: 'due_amount',
                name: 'due_amount',
                searchable: true
            }, 
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            },
        ],
        columnDefs: [{
                targets: 0, // ID (hidden)
                createdCell: function(td) {
                    $(td).attr('data-label', 'ID');
                }
            },
            {
                targets: 1, // Checkbox
                createdCell: function(td) {
                    $(td).attr('data-label', 'Select');
                }
            },
            {
                targets: 2, // Sr no
                createdCell: function(td) {
                    $(td).attr('data-label', 'Sr. No.');
                }
            },
            {
                targets: 3, // Raw Material
                createdCell: function(td) {
                    $(td).attr('data-label', 'Raw Material');
                }
            },
            {
                targets: 4, // Supplier
                createdCell: function(td) {
                    $(td).attr('data-label', 'Supplier');
                }
            },
            {
                targets: 5, // Invoice No
                createdCell: function(td) {
                    $(td).attr('data-label', 'Invoice No');
                }
            },
            {
                targets: 6, // Invoice Date
                createdCell: function(td) {
                    $(td).attr('data-label', 'Invoice Date');
                }
            },
            {
                targets: 7, // Quantity
                createdCell: function(td) {
                    $(td).attr('data-label', 'Quantity');
                }
            },
            {
                targets: 8, // Unit Price
                createdCell: function(td) {
                    $(td).attr('data-label', 'Unit Price');
                }
            },
            {
                targets: 9, // Total Price
                createdCell: function(td) {
                    $(td).attr('data-label', 'Total Price');
                }
            },
            {
                targets: 10, // Status
                createdCell: function(td) {
                    $(td).attr('data-label', 'Status');
                }
            },
            {
                targets: 11, // Paid Amount
                createdCell: function(td) {
                    $(td).attr('data-label', 'Paid Amount');
                }
            },
            {
                targets: 12, // Due Amount
                createdCell: function(td) {
                    $(td).attr('data-label', 'Due Amount');
                }
            },
            {
                targets: 13, // Action
                createdCell: function(td) {
                    $(td).attr('data-label', 'Action');
                }
            },
        ]


    });

    $(document).on('click', '.payment-modal', function() {
        let purchase_id = $(this).data('id');
        $('#paymentModal').modal('show');
    });

    $(document).on('click', '.open-raw-material-details-model', function() {
        let raw_material_id = $(this).data('id');
        $('#rawMaterialDetailsModal').modal('show');
    });

    $(document).on('click', '.open-supplier-details-model', function() {
        let supplier_id = $(this).data('id');
        $('#supplierDetailsModal').modal('show');
    });
</script>
@endsection
