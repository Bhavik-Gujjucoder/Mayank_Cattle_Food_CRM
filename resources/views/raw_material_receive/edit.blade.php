@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('styles')
    @include('raw_material.partials.module-responsive')
@endsection
@section('content')

<div class="card raw-material-module">
    <div class="card-body">
        <form action="{{ route('raw-material.receive.update', $receive->id) }}" method="POST" id="receiveForm">
            @csrf
            @method('PUT')
            <p class="form-section-title"><i class="ti ti-truck-delivery me-1"></i>Received Entry</p>
            <div class="row">
                <div class="col-12 col-md-6 mb-3">
                    <label class="col-form-label">Purchase Order <span class="text-danger">*</span></label>
                    <select name="raw_material_order_id" id="raw_material_order_id" class="form-select search-select">
                        <option value="">-- Select Order --</option>
                        @foreach ($orders as $order)
                            <option value="{{ $order->id }}"
                                {{ old('raw_material_order_id', $receive->raw_material_order_id) == $order->id ? 'selected' : '' }}>
                                @include('raw_material.partials.order-select-label', ['order' => $order])
                            </option>
                        @endforeach
                    </select>
                    <span class="text-danger small raw_material_order_id_error">@error('raw_material_order_id'){{ $message }}@enderror</span>
                </div>
                <div class="col-12 col-md-6 mb-3">
                    <label class="col-form-label">Order Item <span class="text-danger">*</span></label>
                    <select name="raw_material_order_item_id" id="raw_material_order_item_id" class="form-select search-select">
                        <option value="">-- Select Order Item --</option>
                        @foreach ($order_items as $item)
                            <option value="{{ $item->id }}"
                                    data-pending="{{ $item->pending_qty + $receive->qty }}"
                                    {{ old('raw_material_order_item_id', $receive->raw_material_order_item_id) == $item->id ? 'selected' : '' }}>
                                {{ $item->rawMaterial?->name ?? '—' }}
                                (Pending: {{ $item->pending_qty + $receive->qty }} tons)
                            </option>
                        @endforeach
                    </select>
                    <span class="text-danger small raw_material_order_item_id_error">@error('raw_material_order_item_id'){{ $message }}@enderror</span>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <label class="col-form-label">Quantity (tons) <span class="text-danger">*</span></label>
                    <input type="number" name="qty" id="qty" value="{{ old('qty', $receive->qty) }}"
                           class="form-control" min="1" step="1" placeholder="0">
                    <small class="text-muted pending-qty-hint">Max allowed: <span id="pendingQtyVal">—</span> tons</small>
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
<script>
$(document).ready(function () {
    flatpickr('.flatpickr', {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd-m-Y',
        allowInput: true
    });

    $('#raw_material_order_id, #raw_material_order_item_id').select2({ width: '100%' });

    var pendingQty = 0;

    function updatePendingQty() {
        var pending = $('#raw_material_order_item_id option:selected').data('pending');
        pendingQty = parseInt(pending, 10) || 0;
        $('#pendingQtyVal').text(pendingQty > 0 ? pendingQty : '—');
        if (pendingQty > 0) {
            $('#qty').attr('max', pendingQty);
        } else {
            $('#qty').removeAttr('max');
        }
    }

    function loadOrderItems(orderId, selectedItemId) {
        if (!orderId) {
            resetOrderItemSelect();
            return;
        }

        $.get("{{ route('raw-material.order.items', '__ORDER__') }}".replace('__ORDER__', orderId), function (items) {
            var $itemSelect = $('#raw_material_order_item_id');
            var currentVal = selectedItemId || $itemSelect.val();
            $itemSelect.empty().append('<option value="">-- Select Order Item --</option>');

            $.each(items, function (i, item) {
                var $opt = $('<option>', {
                    value: item.id,
                    text: item.label
                }).data('pending', item.pending_qty);

                @if ($receive->raw_material_order_item_id)
                if (String(item.id) === '{{ $receive->raw_material_order_item_id }}') {
                    $opt.data('pending', {{ $receive->qty }} + parseInt(item.pending_qty, 10));
                }
                @endif

                if (currentVal && String(currentVal) === String(item.id)) {
                    $opt.prop('selected', true);
                }
                $itemSelect.append($opt);
            });

            $itemSelect.prop('disabled', false).trigger('change.select2');
            updatePendingQty();
        });
    }

    function resetOrderItemSelect() {
        pendingQty = 0;
        $('#pendingQtyVal').text('—');
        var $itemSelect = $('#raw_material_order_item_id');
        $itemSelect.empty().append('<option value="">-- Select Order Item --</option>');
        $itemSelect.prop('disabled', true).trigger('change.select2');
    }

    function clearFieldErrors() {
        $('.raw_material_order_id_error, .raw_material_order_item_id_error, .qty_error, .freight_error, .received_date_error').text('');
        rmSetInvalid($('#raw_material_order_id'), false);
        rmSetInvalid($('#raw_material_order_item_id'), false);
        rmSetInvalid($('#qty'), false);
        rmSetInvalid($('#freight'), false);
        rmSetInvalid($('#received_date'), false);
    }

    $('#raw_material_order_id').on('change', function () {
        $(this).removeClass('is-invalid');
        $('.raw_material_order_id_error').text('');
        loadOrderItems($(this).val(), null);
    });

    $('#raw_material_order_item_id').on('change', function () {
        $(this).removeClass('is-invalid');
        $('.raw_material_order_item_id_error').text('');
        updatePendingQty();
    });

    $('#qty').on('input', function () {
        $(this).removeClass('is-invalid');
        $('.qty_error').text('');
    });

    $('#freight').on('input', function () {
        $(this).removeClass('is-invalid');
        $('.freight_error').text('');
    });

    $('#received_date').on('change input', function () {
        $(this).removeClass('is-invalid');
        $('.received_date_error').text('');
    });

    function validateForm() {
        clearFieldErrors();
        var isValid = true;

        if (!$('#raw_material_order_id').val()) {
            $('.raw_material_order_id_error').text('Please select a purchase order.');
            rmSetInvalid($('#raw_material_order_id'), true);
            isValid = false;
        }

        if ($('#raw_material_order_item_id').prop('disabled') || !$('#raw_material_order_item_id').val()) {
            $('.raw_material_order_item_id_error').text('Please select an order item.');
            rmSetInvalid($('#raw_material_order_item_id'), true);
            isValid = false;
        }

        var qtyVal = $.trim($('#qty').val());
        if (!qtyVal) {
            $('.qty_error').text('Please enter quantity.');
            rmSetInvalid($('#qty'), true);
            isValid = false;
        } else {
            var qty = parseInt(qtyVal, 10);
            if (qty < 1) {
                $('.qty_error').text('Quantity must be at least 1 ton.');
                rmSetInvalid($('#qty'), true);
                isValid = false;
            } else if (pendingQty > 0 && qty > pendingQty) {
                $('.qty_error').text('Quantity cannot exceed pending quantity (' + pendingQty + ' tons).');
                rmSetInvalid($('#qty'), true);
                isValid = false;
            }
        }

        var freightVal = $.trim($('#freight').val());
        if (freightVal !== '' && parseFloat(freightVal) < 0) {
            $('.freight_error').text('Freight cannot be negative.');
            rmSetInvalid($('#freight'), true);
            isValid = false;
        }

        if (!$.trim($('#received_date').val())) {
            $('.received_date_error').text('Please select received date.');
            rmSetInvalid($('#received_date'), true);
            isValid = false;
        }

        return isValid;
    }

    $('#submitReceiveBtn').on('click', function () {
        if (validateForm()) {
            $('#receiveForm').submit();
        } else {
            rmScrollToFirstInvalid('#receiveForm');
        }
    });

    updatePendingQty();
});
</script>
@endsection
