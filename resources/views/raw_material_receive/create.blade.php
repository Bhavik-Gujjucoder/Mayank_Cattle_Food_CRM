@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('styles')
    @include('raw_material.partials.module-responsive')
@endsection
@section('content')

@php
    $selectedBrokerId = old('receive_broker_id', '');
    $selectedSupplierId = old('receive_supplier_id', '');
    $selectedOrderId = old('raw_material_order_id', '');
@endphp

<div class="card raw-material-module">
    <div class="card-body">
        <form action="{{ route('raw-material.receive.store') }}" method="POST" id="receiveForm">
            @csrf
            <p class="form-section-title"><i class="ti ti-truck-delivery me-1"></i>Received Entry</p>
            <div class="row">
                @include('raw_material_receive.partials.broker-supplier-order-fields', [
                    'selectedBrokerId' => $selectedBrokerId,
                    'selectedSupplierId' => $selectedSupplierId,
                ])
                <div class="col-12 col-md-6 mb-3">
                    <label class="col-form-label">Order Item <span class="text-danger">*</span></label>
                    <select name="raw_material_order_item_id" id="raw_material_order_item_id" class="form-select search-select" disabled>
                        <option value="">-- Select Order First --</option>
                    </select>
                    <span class="text-danger small raw_material_order_item_id_error">@error('raw_material_order_item_id'){{ $message }}@enderror</span>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label class="col-form-label">Quantity (tons) <span class="text-danger">*</span></label>
                    <input type="number" name="qty" id="qty" value="{{ old('qty') }}"
                           class="form-control" min="1" step="1" placeholder="0">
                    <small class="text-muted pending-qty-hint">
                        Pending: <span id="pendingQtyVal">—</span> tons
                        <span class="text-muted">(after On Road + Received)</span>
                    </small>
                    <small class="text-warning d-none" id="extraQtyHint">Extra qty this entry: <span id="extraQtyVal">0</span> tons</small>
                    <span class="text-danger small qty_error">@error('qty'){{ $message }}@enderror</span>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label class="col-form-label">Freight per ton (₹)</label>
                    <input type="number" name="freight" id="freight" value="{{ old('freight', 0) }}"
                           class="form-control" min="0" step="0.001" placeholder="0.00">
                    <small class="text-muted">Applied to item freight as: freight × qty (tons)</small>
                    <span class="text-danger small freight_error">@error('freight'){{ $message }}@enderror</span>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label class="col-form-label">Received Date <span class="text-danger">*</span></label>
                    <div class="icon-form">
                        <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                        <input type="text" name="received_date" id="received_date"
                               value="{{ old('received_date', now()->format('Y-m-d')) }}"
                               class="form-control flatpickr"
                               placeholder="DD-MM-YYYY" autocomplete="off">
                    </div>
                    <span class="text-danger small received_date_error">@error('received_date'){{ $message }}@enderror</span>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label class="col-form-label">Status <span class="text-danger">*</span></label>
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <input type="radio" id="status_onroad" name="status" value="0"
                                   {{ old('status', '0') == '0' ? 'checked' : '' }}>
                            <label for="status_onroad">On Road</label>
                        </div>
                        <div>
                            <input type="radio" id="status_received" name="status" value="1"
                                   {{ old('status') == '1' ? 'checked' : '' }}>
                            <label for="status_received">Received</label>
                        </div>
                    </div>
                    <span class="text-danger small status_error">@error('status'){{ $message }}@enderror</span>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-end gap-2 mt-3 rm-form-actions">
                <a href="{{ route('raw-material.receive.index') }}" class="btn btn-light px-4">Cancel</a>
                <button type="button" class="btn btn-primary px-5" id="submitReceiveBtn">Save Entry</button>
            </div>
        </form>
    </div>
</div>

@endsection
@section('script')
@include('raw_material.partials.form-validation-script')
@include('raw_material_receive.partials.cascade-scripts', [
    'excludeReceiveId' => null,
    'initialOrderId' => $selectedOrderId,
    'initialItemId' => old('raw_material_order_item_id', ''),
    'receivableOrders' => $receivable_orders,
    'allSuppliers' => $suppliers,
])
<script>
$(document).ready(function () {
    flatpickr('.flatpickr', {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd-m-Y',
        allowInput: true
    });

    initReceiveCascadeForm({
        requireStatus: true,
        submitBtn: '#submitReceiveBtn'
    });
});
</script>
@endsection
