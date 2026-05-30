@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('content')

<div class="card">
    <div class="card-body">
        <form action="{{ route('raw-material-receive.store') }}" method="POST" id="receiveForm">
            @csrf
            <p class="form-section-title"><i class="ti ti-truck-delivery me-1"></i>Received Entry</p>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="col-form-label">Purchase Order <span class="text-danger">*</span></label>
                    <select name="raw_material_order_id" id="raw_material_order_id" class="form-select search-select">
                        <option value="">-- Select Order --</option>
                        @foreach ($orders as $order)
                            <option value="{{ $order->id }}" {{ old('raw_material_order_id') == $order->id ? 'selected' : '' }}>
                                {{ $order->order_unique_id }}
                                @if ($order->supplier)
                                    — {{ $order->supplier->name }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('raw_material_order_id')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="col-form-label">Order Item <span class="text-danger">*</span></label>
                    <select name="raw_material_order_item_id" id="raw_material_order_item_id" class="form-select search-select" disabled>
                        <option value="">-- Select Order First --</option>
                    </select>
                    @error('raw_material_order_item_id')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Quantity (tons) <span class="text-danger">*</span></label>
                    <input type="number" name="qty" id="qty" value="{{ old('qty') }}"
                           class="form-control @error('qty') is-invalid @enderror" min="1" step="1" placeholder="0">
                    <small class="text-muted pending-qty-hint">Pending: <span id="pendingQtyVal">—</span> tons</small>
                    @error('qty')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Freight per ton (₹)</label>
                    <input type="number" name="freight" id="freight" value="{{ old('freight', 0) }}"
                           class="form-control @error('freight') is-invalid @enderror" min="0" step="0.001" placeholder="0.000">
                    <small class="text-muted">Applied to item freight as: freight × qty (tons)</small>
                    @error('freight')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Received Date <span class="text-danger">*</span></label>
                    <div class="icon-form">
                        <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
                        <input type="text" name="received_date" id="received_date"
                               value="{{ old('received_date', now()->format('Y-m-d')) }}"
                               class="form-control flatpickr @error('received_date') is-invalid @enderror"
                               placeholder="DD-MM-YYYY" autocomplete="off">
                    </div>
                    @error('received_date')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-4 mb-3">
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
                    @error('status')
                        <span class="text-danger small d-block">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-end gap-2 mt-3">
                <a href="{{ route('raw-material-receive.index') }}" class="btn btn-light px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-5">Save Entry</button>
            </div>
        </form>
    </div>
</div>

@endsection
@section('script')
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

    function resetOrderItemSelect() {
        pendingQty = 0;
        $('#pendingQtyVal').text('—');
        var $itemSelect = $('#raw_material_order_item_id');
        $itemSelect.empty().append('<option value="">-- Select Order Item --</option>');
        $itemSelect.prop('disabled', true).trigger('change.select2');
    }

    function loadOrderItems(orderId, selectedItemId) {
        resetOrderItemSelect();
        if (!orderId) return;

        $.get("{{ route('raw-material-order.items', '__ORDER__') }}".replace('__ORDER__', orderId), function (items) {
            var $itemSelect = $('#raw_material_order_item_id');
            $itemSelect.empty().append('<option value="">-- Select Order Item --</option>');

            if (!items.length) {
                $itemSelect.append('<option value="">No pending items</option>');
                $itemSelect.prop('disabled', true).trigger('change.select2');
                return;
            }

            $.each(items, function (i, item) {
                var $opt = $('<option>', {
                    value: item.id,
                    text: item.label
                }).data('pending', item.pending_qty);
                if (selectedItemId && String(selectedItemId) === String(item.id)) {
                    $opt.prop('selected', true);
                }
                $itemSelect.append($opt);
            });

            $itemSelect.prop('disabled', false).trigger('change.select2');

            if (selectedItemId) {
                updatePendingQty();
            }
        }).fail(function () {
            show_error('Failed to load order items.');
        });
    }

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

    $('#raw_material_order_id').on('change', function () {
        loadOrderItems($(this).val(), null);
    });

    $('#raw_material_order_item_id').on('change', function () {
        updatePendingQty();
    });

    @if (old('raw_material_order_id'))
        loadOrderItems('{{ old('raw_material_order_id') }}', '{{ old('raw_material_order_item_id') }}');
    @endif
});
</script>
@endsection
