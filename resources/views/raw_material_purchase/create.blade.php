@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('content')
    <div class="card">
        <div class="card-body">
            <form id="purchaseForm" action="{{ route('raw-material-order.store') }}" method="POST">
                @csrf
                @if ($errors->any())
                    {{-- {{ dump($errors->first()) }} --}}
                @endif
                <div class="row">
                    {{-- ── Raw Material Order ID ──────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Order ID <span class="text-danger">*</span></label>
                        <input type="text" name="invoice_no" id="invoice_no" value="RAW-ORDER-0001"
                            class="form-control @error('invoice_no') is-invalid @enderror"
                            placeholder="Enter Invoice No" maxlength="255">
                        @error('invoice_no')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ── Supplier ─────────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" id="supplier_id"
                            class="form-select search-select @error('supplier_id') is-invalid @enderror">
                            <option value="">-- Select Supplier --</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    {{-- ── Transporter ─────────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Transporter <span class="text-danger">*</span></label>
                        <select name="dealer_id" id="dealer_id" class="form-control form-select search-dropdown">
                            <option value="">Select</option>
                            <option value="1">Tirupati Transport</option>
                            <option value="1">Radhe Transport</option>
                            <option value="1">Maruti Transport</option>
                        </select>
                    </div>
                    {{-- ── Order Date ──────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Order Date <span class="text-danger">*</span></label>
                        <div class="icon-form">
                            <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                            <input type="text" name="order_date" value="" id="datePicker"
                                class="form-control" placeholder="Order Date">
                        </div>
                    </div>
                    {{-- ── Expected Delivery Date ──────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Expected Delivery Date <span class="text-danger">*</span></label>
                        <div class="icon-form">
                            <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                            <input type="text" name="order_date" value="" id="datePicker"
                                class="form-control" placeholder="Order Date">
                        </div>
                    </div>
                    {{-- ── Delivery Location ──────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Delivery Location <span class="text-danger">*</span></label>
                        <input type="text" name="transport" value="" class="form-control"
                            placeholder="Purchase Location">
                    </div>
                    {{-- ── Invoice No ──────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Invoice No <span class="text-danger">*</span></label>
                        <input type="text" name="invoice_no" id="invoice_no" value="{{ old('invoice_no') }}"
                            class="form-control @error('invoice_no') is-invalid @enderror"
                            placeholder="Enter Invoice No" maxlength="255">
                        @error('invoice_no')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ── Invoice Date ────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Invoice Date <span class="text-danger">*</span></label>
                        <input type="text" name="invoice_date" id="invoice_date" value="{{ old('invoice_date') }}"
                            class="form-control @error('invoice_date') is-invalid @enderror"
                            placeholder="Select Invoice Date" maxlength="255">
                        @error('invoice_date')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ── Quantity ──────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="quantity" value="{{ old('quantity', 0) }}"
                            class="form-control @error('quantity') is-invalid @enderror"
                            placeholder="Enter Quantity" maxlength="255">
                        @error('quantity')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ── Unit Price ──────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Unit Price <span class="text-danger">*</span></label>
                        <input type="number" name="unit_price" id="unit_price" value="{{ old('unit_price', 0) }}"
                            class="form-control @error('unit_price') is-invalid @enderror"
                            placeholder="Enter Unit Price" maxlength="255">
                        @error('unit_price')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ── Total Price ──────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Total Price <span class="text-danger">*</span></label>
                        <input type="number" name="total_price" id="total_price" value="{{ old('total_price',0) }}"
                            class="form-control @error('total_price') is-invalid @enderror"
                            placeholder="Enter Total Price" maxlength="255" disabled>
                        @error('total_price')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ── Paid Amount ──────────────────────────────── --}}
                    <!--<div class="col-md-4 mb-3">
                        <label class="col-form-label">Paid Amount <span class="text-danger">*</span></label>
                        <input type="number" name="paid_amount" id="paid_amount" value="{{ old('paid_amount', 0) }}"
                            class="form-control @error('paid_amount') is-invalid @enderror"
                            placeholder="Enter Paid Amount" maxlength="255">
                        @error('paid_amount')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>-->

                    {{-- ── Due Amount ──────────────────────────────── --}}
                    <!--<div class="col-md-4 mb-3">
                        <label class="col-form-label">Due Amount <span class="text-danger">*</span></label>
                        <input type="number" name="due_amount" id="due_amount" value="{{ old('due_amount', 0) }}"
                            class="form-control @error('due_amount') is-invalid @enderror"
                            placeholder="Enter Due Amount" maxlength="255">
                        @error('due_amount')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>-->

                    {{-- ── Status ──────────────────────────────────────── --}}
                    <!--<div class="col-md-4 mb-3">
                        <label class="col-form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" id="status"
                            class="form-select search-select @error('status') is-invalid @enderror">
                            <option value="">-- Select Status --</option>
                            @foreach (rawMaterialPurchaseStatus() as $status_key => $status_value)
                                <option value="{{ $status_key }}" {{ old('status') == $status_key ? 'selected' : (0 == $status_key ? 'selected' : '') }}>
                                    {{ $status_value }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>-->

                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Priority <span class="text-danger">*</span></label>
                        <select name="dealer_id" id="dealer_id" class="form-control form-select search-dropdown">
                            <option value="">Select</option>
                            <option value="1">Normal</option>
                            <option value="1">Medium</option>
                            <option value="1">High</option>
                        </select>
                    </div>

                    {{-- ── Remarks ─────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Remarks</label>
                        <textarea name="remarks" id="remarks"
                            class="form-control @error('remarks') is-invalid @enderror" placeholder="Enter Remarks" rows="2"
                            >{{ old('remarks') }}</textarea>
                    </div>


                </div>{{-- /.row --}}
                {{-- ── Product Table ──────────────────────────────── --}}
                <div class="table-responsive gc-order-management-table">
                    <table class="table table-view addnewfield">
                        <thead>
                            <tr>
                                <th scope="col">S.No </th>
                                <th scope="col">Raw Material <span class="text-danger">*</span></th>
                                <th scope="col">GST(%)</th>
                                <th scope="col">QTY <span class="text-danger">*</span></th>
                                <th scope="col">Unit Price <span class="text-danger">*</span></th>
                                <th scope="col">Total Price <span class="text-danger">*</span></th>
                                <th scope="col">Action </th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <tr class="field-group">
                                <td data-label="S.No.">1</td>
                                <td data-label="Product Name">
                                    <select name="raw_material_id" id="raw_material_id"
                                        class="form-select search-select @error('raw_material_id') is-invalid @enderror">
                                        <option value="">Select</option>
                                        @foreach ($raw_materials as $material)
                                            <option value="{{ $material->id }}" {{ old('raw_material_id') == $material->id ? 'selected' : '' }}>
                                                {{ $material->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td data-label="GST">
                                    <input type="number" name="gst[]" value="" class="form-control gst-field"
                                        readonly placeholder="GST">
                                </td>
                                <td data-label="QTY">
                                    <input type="number" name="qty[]" value="{{ old('qty') }}"
                                        class="form-control product-field qty-field" placeholder="QTY">
                                </td>
                                <td data-label="Price">
                                    <input type="number" name="price[]" value="{{ old('price') }}" readonly
                                        class="form-control product-field price-field" placeholder="Price">
                                </td>
                                <td data-label="Total">
                                    <input type="number" name="total[]" class="form-control total-field" readonly
                                        placeholder="Total">
                                </td>
                                <td data-label="Action">
                                    <button type="button" onclick="addpropRow()" class="btn btn-primary">Add
                                        New</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div id="productError" class="text-danger mb-3" style="display:none;">
                        Please fill all fields in each product row.
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-2">
                    <a href="{{ route('raw-material-order.index') }}" class="btn btn-light me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Purchase</button>
                </div>

            </form>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $(document).ready(function() {
            /* Invoice Date Field */
            flatpickr("#invoice_date", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                time_24hr: true
            });

            /* Quantity and Unit Price change */
            $('#quantity, #unit_price').on('keyup', function() {
                var quantity = $('#quantity').val();
                var unit_price = $('#unit_price').val();
                var total_price = quantity * unit_price;
                $('#total_price').val(total_price);
            });

            
            $('#purchaseForm').on('submit', function () {
                $('#total_price').prop('disabled', false);
            });
        });
    </script>
@endsection
