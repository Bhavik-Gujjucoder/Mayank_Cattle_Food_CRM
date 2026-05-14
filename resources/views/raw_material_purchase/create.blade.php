@extends('layouts.main')
@section('title') {{ $page_title }} @endsection
@section('content')
    <div class="card">
        <div class="card-body">
            <form id="purchaseForm" action="{{ route('raw-material-order.store') }}" method="POST">
                @csrf
                <div class="row">

                    {{-- ── Raw Material ──────────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Raw Material <span class="text-danger">*</span></label>
                        <select name="raw_material_id" id="raw_material_id"
                            class="form-select search-select @error('raw_material_id') is-invalid @enderror">
                            <option value="">-- Select Raw Material --</option>
                            @foreach ($raw_materials as $material)
                                <option value="{{ $material->id }}"
                                    {{ old('raw_material_id') == $material->id ? 'selected' : '' }}>
                                    {{ $material->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('raw_material_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ── Supplier ─────────────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" id="supplier_id"
                            class="form-select search-select @error('supplier_id') is-invalid @enderror">
                            <option value="">-- Select Supplier --</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                    {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ── Invoice No ───────────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Invoice No <span class="text-danger">*</span></label>
                        <input type="text" name="invoice_no" id="invoice_no"
                            value="{{ old('invoice_no') }}"
                            class="form-control @error('invoice_no') is-invalid @enderror"
                            placeholder="Enter Invoice No" maxlength="255">
                        @error('invoice_no')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ── Invoice Date ─────────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Invoice Date <span class="text-danger">*</span></label>
                        <div class="icon-form">
                            <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                            <input type="text" name="invoice_date" id="invoice_date"
                                value="{{ old('invoice_date') }}"
                                class="form-control @error('invoice_date') is-invalid @enderror"
                                placeholder="Select Invoice Date">
                        </div>
                        @error('invoice_date')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ── Quantity ─────────────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="quantity"
                            value="{{ old('quantity', 0) }}"
                            class="form-control @error('quantity') is-invalid @enderror"
                            placeholder="Enter Quantity" min="0.01" step="0.01">
                        @error('quantity')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ── Unit Price ───────────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Unit Price (₹) <span class="text-danger">*</span></label>
                        <input type="number" name="unit_price" id="unit_price"
                            value="{{ old('unit_price', 0) }}"
                            class="form-control @error('unit_price') is-invalid @enderror"
                            placeholder="Enter Unit Price" min="0.01" step="0.01">
                        @error('unit_price')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ── Total Price (auto-calculated, read-only) ─────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Total Price (₹)</label>
                        <input type="number" id="total_price_display"
                            value="{{ old('quantity', 0) * old('unit_price', 0) }}"
                            class="form-control" placeholder="Auto-calculated" readonly>
                    </div>

                    {{-- ── Remarks ──────────────────────────────────────────── --}}
                    <div class="col-md-4 mb-3">
                        <label class="col-form-label">Remarks</label>
                        <textarea name="remarks" id="remarks"
                            class="form-control @error('remarks') is-invalid @enderror"
                            placeholder="Enter Remarks" rows="2" maxlength="1000">{{ old('remarks') }}</textarea>
                        @error('remarks')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                </div>{{-- /.row --}}

                <div class="d-flex justify-content-end mt-2">
                    <a href="{{ route('raw-material-order.index') }}" class="btn btn-light me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Purchase Order</button>
                </div>

            </form>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $(document).ready(function () {

            /* Invoice Date picker */
            flatpickr('#invoice_date', {
                dateFormat: 'Y-m-d',
            });

            /* Auto-calculate total price */
            function recalcTotal() {
                var qty   = parseFloat($('#quantity').val())   || 0;
                var price = parseFloat($('#unit_price').val()) || 0;
                $('#total_price_display').val((qty * price).toFixed(2));
            }

            $('#quantity, #unit_price').on('input', recalcTotal);
            recalcTotal();
        });
    </script>
@endsection
