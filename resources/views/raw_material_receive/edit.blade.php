@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('content')

<div class="card">
    <div class="card-body">
        <form action="{{ route('raw-material-receive.update', $receive->id) }}" method="POST" id="receiveForm">
            @csrf
            @method('PUT')
            <p class="form-section-title"><i class="ti ti-truck-delivery me-1"></i>Received Entry</p>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="col-form-label">Purchase Order <span class="text-danger">*</span></label>
                    <select name="raw_material_order_id" id="raw_material_order_id" class="form-select search-select">
                        <option value="">-- Select Order --</option>
                        @foreach ($orders as $order)
                            <option value="{{ $order->id }}"
                                {{ old('raw_material_order_id', $receive->raw_material_order_id) == $order->id ? 'selected' : '' }}>
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
                    @error('raw_material_order_item_id')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Quantity (tons) <span class="text-danger">*</span></label>
                    <input type="number" name="qty" id="qty" value="{{ old('qty', $receive->qty) }}"
                           class="form-control @error('qty') is-invalid @enderror" min="1" step="1" placeholder="0">
                    <small class="text-muted pending-qty-hint">Max allowed: <span id="pendingQtyVal">—</span> tons</small>
                    @error('qty')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Freight per ton (₹)</label>
                    <input type="number" name="freight" id="freight" value="{{ old('freight', $receive->freight) }}"
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
                               value="{{ old('received_date', $receive->received_date?->format('Y-m-d')) }}"
                               class="form-control flatpickr @error('received_date') is-invalid @enderror"
                               placeholder="DD-MM-YYYY" autocomplete="off">
                    </div>
                    @error('received_date')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="col-form-label">Status</label>
                    <div class="fw-semibold">{!! $receive->statusBadge() !!}</div>
                    <input type="hidden" name="status" value="0">
                    <small class="text-muted">Only on-road entries can be edited.</small>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-end gap-2 mt-3">
                <a href="{{ route('raw-material-receive.index') }}" class="btn btn-light px-4">Cancel</a>
                <button type="submit" class="btn btn-primary px-5">Update Entry</button>
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

    function updatePendingQty() {
        var pending = $('#raw_material_order_item_id option:selected').data('pending');
        var pendingQty = parseInt(pending, 10) || 0;
        $('#pendingQtyVal').text(pendingQty > 0 ? pendingQty : '—');
        if (pendingQty > 0) {
            $('#qty').attr('max', pendingQty);
        }
    }

    function loadOrderItems(orderId, selectedItemId) {
        if (!orderId) return;

        $.get("{{ route('raw-material-order.items', '__ORDER__') }}".replace('__ORDER__', orderId), function (items) {
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

            $itemSelect.trigger('change.select2');
            updatePendingQty();
        });
    }

    $('#raw_material_order_item_id').on('change', updatePendingQty);

    $('#raw_material_order_id').on('change', function () {
        loadOrderItems($(this).val(), null);
    });

    updatePendingQty();
});
</script>
@endsection
