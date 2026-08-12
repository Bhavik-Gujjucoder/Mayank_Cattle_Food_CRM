@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('styles')
    @include('raw_material.partials.module-responsive')
@endsection
@section('content')

@php
    $selectedBrokerId = old('receive_broker_id', $receive->order?->supplier_broker_id);
    $selectedSupplierId = old('receive_supplier_id', $receive->order?->supplier_id);
    $selectedOrderId = old('raw_material_order_id', $receive->raw_material_order_id);
    $selectedItemId = old('raw_material_order_item_id', $receive->raw_material_order_item_id);
@endphp

<div class="card raw-material-module">
    <div class="card-body">
        <form action="{{ route('raw-material.receive.update', $receive->id) }}" method="POST" id="receiveForm">
            @csrf
            @method('PUT')
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
                    <input type="number" name="qty" id="qty" value="{{ old('qty', $receive->qty) }}"
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
                    <input type="number" name="freight" id="freight" value="{{ old('freight', $receive->freight) }}"
                           class="form-control" min="0" step="0.001" placeholder="0.00">
                    <small class="text-muted">Applied to item freight as: freight × qty (tons)</small>
                    <span class="text-danger small freight_error">@error('freight'){{ $message }}@enderror</span>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label class="col-form-label">Received Date <span class="text-danger">*</span></label>
                    <div class="icon-form">
                        <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                        <input type="text" name="received_date" id="received_date"
                               value="{{ old('received_date', $receive->received_date?->format('Y-m-d')) }}"
                               class="form-control flatpickr"
                               placeholder="DD-MM-YYYY" autocomplete="off">
                    </div>
                    <span class="text-danger small received_date_error">@error('received_date'){{ $message }}@enderror</span>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label class="col-form-label">Status</label>
                    <div class="fw-semibold">{!! $receive->statusBadge() !!}</div>
                    <input type="hidden" name="status" value="0">
                    <small class="text-muted">Only on-road entries can be edited.</small>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-end gap-2 mt-3 rm-form-actions">
                <a href="{{ route('raw-material.receive.index') }}" class="btn btn-light px-4">Cancel</a>
                <button type="button" class="btn btn-primary px-5" id="submitReceiveBtn">Update Entry</button>
            </div>
        </form>
    </div>
</div>

@endsection
@section('script')
@include('raw_material.partials.form-validation-script')
@include('raw_material_receive.partials.cascade-scripts', [
    'excludeReceiveId' => $receive->id,
    'initialOrderId' => $selectedOrderId,
    'initialItemId' => $selectedItemId,
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
        requireStatus: false,
        submitBtn: '#submitReceiveBtn'
    });
});
</script>
@endsection
