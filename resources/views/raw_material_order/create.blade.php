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
        <div class="row">
            <div class="col-12 col-md-4 mb-3">
                <label class="col-form-label">Order ID</label>
                <input type="text" name="order_unique_id" id="order_unique_id"
                       value="{{ old('order_unique_id', $order_unique_id) }}"
                       class="form-control fw-semibold" readonly>
            </div>
            <div class="col-12 col-md-4 mb-3">
                <label class="col-form-label">Supplier <span class="text-danger">*</span></label>
                <select name="supplier_id" id="supplier_id" class="form-select search-select">
                    <option value="">-- Select Supplier --</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
                <span class="text-danger small supplier_id_error">@error('supplier_id'){{ $message }}@enderror</span>
            </div>
            <div class="col-12 col-md-4 mb-3">
                <label class="col-form-label">Order Date <span class="text-danger">*</span></label>
                <div class="icon-form">
                    <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                    <input type="text" name="order_date" id="order_date"
                           value="{{ old('order_date', now()->format('Y-m-d')) }}"
                           class="form-control flatpickr" placeholder="DD-MM-YYYY" autocomplete="off">
                </div>
                <span class="text-danger small order_date_error">@error('order_date'){{ $message }}@enderror</span>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <p class="form-section-title"><i class="ti ti-packages me-1"></i>Material Items</p>
        <div class="table-responsive">
            <table class="table table-bordered order-product-table" id="itemTable">
                <thead>
                    <tr>
                        <th style="width:50px;">S.No</th>
                        <th style="min-width:200px;">Raw Material <span class="text-danger">*</span></th>
                        <th style="width:120px;">Total Qty (tons) <span class="text-danger">*</span></th>
                        <th style="width:160px;">Price / kg <span class="text-danger">*</span></th>
                        <th style="width:160px;">Total Price</th>
                        <th style="width:100px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="itemTableBody">
                    <tr class="item-row">
                        <td class="row-index text-center fw-semibold">1</td>
                        <td>
                            <select name="raw_material_id[]" class="form-select material-select" style="min-width:180px;">
                                <option value="">-- Select Material --</option>
                                @foreach ($raw_materials as $material)
                                    <option value="{{ $material->id }}"
                                            data-price="{{ $material->last_purchase_price }}">
                                        {{ $material->name }} ({{ $material->unit }})
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="number" name="total_qty[]" class="form-control qty-field"
                                   placeholder="0" min="1" step="1">
                        </td>
                        <td>
                            <input type="number" name="price[]" class="form-control price-field"
                                   placeholder="0.00" min="0" step="0.001">
                        </td>
                        <td>
                            <input type="text" class="form-control total-field" placeholder="0.00" readonly>
                        </td>
                        <td class="text-center row-actions">
                            <button type="button" class="btn btn-primary btn-sm" id="addRowBtn">
                                <i class="ti ti-plus me-1"></i>Add New
                            </button>
                            <button type="button" class="btn-remove-row remove-row-btn" title="Remove row" style="display:none;">
                                <i class="ti ti-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="item-row-error" style="display:none;">
                        <td colspan="6" class="pt-0 pb-2 border-top-0">
                            <small class="text-danger">
                                <i class="ti ti-alert-circle me-1"></i>All fields are required.
                            </small>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<template id="itemRowTpl">
    <tr class="item-row">
        <td class="row-index text-center fw-semibold"></td>
        <td>
            <select name="raw_material_id[]" class="form-select material-select" style="min-width:180px;">
                <option value="">-- Select Material --</option>
                @foreach ($raw_materials as $material)
                    <option value="{{ $material->id }}" data-price="{{ $material->last_purchase_price }}">
                        {{ $material->name }} ({{ $material->unit }})
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" name="total_qty[]" class="form-control qty-field" placeholder="0" min="1" step="1">
        </td>
        <td>
            <input type="number" name="price[]" class="form-control price-field" placeholder="0.00" min="0" step="0.001">
        </td>
        <td>
            <input type="text" class="form-control total-field" placeholder="0.00" readonly>
        </td>
        <td class="text-center row-actions">
            <button type="button" class="btn btn-primary btn-sm" id="addRowBtn">
                <i class="ti ti-plus me-1"></i>Add New
            </button>
            <button type="button" class="btn-remove-row remove-row-btn" title="Remove row" style="display:none;">
                <i class="ti ti-trash"></i>
            </button>
        </td>
    </tr>
    <tr class="item-row-error" style="display:none;">
        <td colspan="6" class="pt-0 pb-2 border-top-0">
            <small class="text-danger">
                <i class="ti ti-alert-circle me-1"></i>All fields are required.
            </small>
        </td>
    </tr>
</template>

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
$(document).ready(function () {
    flatpickr('.flatpickr', {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd-m-Y',
        allowInput: true
    });

    $('#supplier_id').select2({ width: '100%' });
    initMaterialSelect($('#itemTableBody .material-select'));

    function initMaterialSelect($el) {
        $el.select2({ width: '100%' });
    }

    function updateRowButtons() {
        var $rows = $('#itemTableBody .item-row');
        $rows.find('#addRowBtn').hide();
        $rows.last().find('#addRowBtn').show();
        if ($rows.length > 1) {
            $rows.find('.remove-row-btn').show();
        } else {
            $rows.find('.remove-row-btn').hide();
        }
    }

    function reindexRows() {
        $('#itemTableBody .item-row').each(function (i) {
            $(this).find('.row-index').text(i + 1);
        });
    }

    function rowTotal(qty, price) {
        return (parseFloat(qty) || 0) * 1000 * (parseFloat(price) || 0);
    }

    function calculateTotals() {
        var totalQty = 0;
        var grandTotal = 0;
        $('#itemTableBody .item-row').each(function () {
            var qty = parseFloat($(this).find('.qty-field').val()) || 0;
            var price = parseFloat($(this).find('.price-field').val()) || 0;
            var lineTotal = rowTotal(qty, price);
            $(this).find('.total-field').val(lineTotal > 0 ? lineTotal.toFixed(2) : '');
            totalQty += qty;
            grandTotal += lineTotal;
        });
        $('#display_total_qty').text(totalQty);
        $('#display_grand_total').text(grandTotal.toFixed(2));
    }

    $(document).on('click', '#addRowBtn', function () {
        var tplNode = document.getElementById('itemRowTpl');
        var $nodes = $(tplNode.content.cloneNode(true).children);
        $('#itemTableBody').append($nodes);
        updateRowButtons();
        reindexRows();
        var $newSelect = $('#itemTableBody .item-row:last .material-select');
        initMaterialSelect($newSelect);
        $newSelect[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    $(document).on('click', '.remove-row-btn', function () {
        var $row = $(this).closest('tr');
        $row.next('.item-row-error').remove();
        $row.find('.material-select').select2('destroy');
        $row.remove();
        reindexRows();
        updateRowButtons();
        calculateTotals();
    });

    $(document).on('change', '.material-select', function () {
        var $row = $(this).closest('tr');
        var price = parseFloat($(this).find(':selected').data('price')) || 0;
        if (price > 0) {
            $row.find('.price-field').val(price.toFixed(2));
        }
        $(this).removeClass('is-invalid');
        calculateTotals();
    });

    $(document).on('input', '.qty-field, .price-field', function () {
        $(this).removeClass('is-invalid');
        var $row = $(this).closest('tr');
        if (!$row.find('.is-invalid').length) {
            $row.next('.item-row-error').hide();
        }
        calculateTotals();
    });

    function validateForm() {
        var isValid = true;
        $('.supplier_id_error, .order_date_error').text('');
        rmSetInvalid($('#supplier_id'), false);
        rmSetInvalid($('#order_date'), false);
        $('.item-row-error').hide();
        $('#itemTableBody .item-row').each(function () {
            var $row = $(this);
            rmSetInvalid($row.find('.material-select'), false);
            rmSetInvalid($row.find('.qty-field'), false);
            rmSetInvalid($row.find('.price-field'), false);
        });

        if (!$('#supplier_id').val()) {
            $('.supplier_id_error').text('Please select a supplier.');
            rmSetInvalid($('#supplier_id'), true);
            isValid = false;
        }
        if (!$.trim($('#order_date').val())) {
            $('.order_date_error').text('Please select an order date.');
            rmSetInvalid($('#order_date'), true);
            isValid = false;
        }

        $('#itemTableBody .item-row').each(function () {
            var $row = $(this);
            var materialId = $row.find('.material-select').val();
            var qty = $.trim($row.find('.qty-field').val());
            var price = $.trim($row.find('.price-field').val());
            if (!materialId || !qty || !price) {
                if (!materialId) rmSetInvalid($row.find('.material-select'), true);
                if (!qty) rmSetInvalid($row.find('.qty-field'), true);
                if (!price) rmSetInvalid($row.find('.price-field'), true);
                $row.next('.item-row-error').show();
                isValid = false;
            }
        });

        return isValid;
    }

    $('#submitOrderBtn').on('click', function () {
        if (validateForm()) {
            $('#rmOrderForm').submit();
        } else {
            rmScrollToFirstInvalid('#rmOrderForm');
        }
    });

    updateRowButtons();
    calculateTotals();
});
</script>
@endsection
