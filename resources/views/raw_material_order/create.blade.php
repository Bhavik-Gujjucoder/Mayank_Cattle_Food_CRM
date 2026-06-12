@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('styles')
    @include('raw_material.partials.module-responsive')
@endsection
@section('content')

<div class="raw-material-module">
<form action="{{ route('raw-material.order.store') }}" id="rmOrderForm" method="POST">
@csrf

<div class="card mb-3">
    <div class="card-body">
        <p class="form-section-title"><i class="ti ti-file-description me-1"></i>Order Information</p>
        @include('raw_material_order.partials.order-header-fields', ['order' => null])
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <p class="form-section-title"><i class="ti ti-packages me-1"></i>Material Items</p>
        @include('raw_material_order.partials.order-items-table')
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <p class="form-section-title text-end"><i class="ti ti-calculator me-1"></i>Order Summary</p>
        <div class="totals-box ms-auto" style="max-width:360px;">
            <div class="totals-row">
                <span class="totals-label">Total Qty (tons)</span>
                <span class="totals-value"><span id="display_total_qty">0</span></span>
            </div>
            <div class="totals-row totals-grand d-flex justify-content-between align-items-center">
                <span class="totals-label-grand">Grand Total</span>
                <span class="totals-value-grand">₹ <span id="display_grand_total">0.00</span></span>
            </div>
        </div>
    </div>
</div>

<div class="d-flex align-items-center justify-content-end gap-2 mb-4 rm-form-actions">
    <a href="{{ route('raw-material.order.index') }}" class="btn btn-light px-4">Cancel</a>
    <button type="button" class="btn btn-primary px-5" id="submitOrderBtn">Create Order</button>
</div>

</form>
</div>

@endsection
@section('script')
@include('raw_material.partials.form-validation-script')
<script>
window.rmOrderFormConfig = @json($order_form_config);
</script>
<script src="{{ asset('assets/js/raw-material-order-form.js') }}"></script>
<script>
$(function () { initRmOrderForm(window.rmOrderFormConfig); });
</script>
@endsection
