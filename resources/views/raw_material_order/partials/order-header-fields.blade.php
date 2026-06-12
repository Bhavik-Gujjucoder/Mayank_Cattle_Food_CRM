@php
    $selectedBrokerId = old('supplier_broker_id', $order->supplier_broker_id ?? '');
@endphp
<div class="row">
    <div class="col-12 col-md-4 mb-3">
        <label class="col-form-label">Order ID</label>
        @if (!empty($order))
            <input type="text" value="{{ $order->order_unique_id }}" class="form-control fw-semibold" readonly>
            <input type="hidden" name="order_unique_id" value="{{ $order->order_unique_id }}">
        @else
            <input type="text" name="order_unique_id" id="order_unique_id"
                   value="{{ old('order_unique_id', $order_unique_id) }}"
                   class="form-control fw-semibold" readonly>
        @endif
    </div>
    <div class="col-12 col-md-4 mb-3">
        <label class="col-form-label">Supplier Broker <span class="text-danger">*</span></label>
        <select name="supplier_broker_id" id="supplier_broker_id" class="form-select search-select">
            <option value="">-- Select Supplier Broker --</option>
            @foreach ($supplier_brokers as $broker)
                <option value="{{ $broker->id }}"
                    {{ old('supplier_broker_id', $order->supplier_broker_id ?? '') == $broker->id ? 'selected' : '' }}>
                    {{ $broker->name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger small supplier_broker_id_error">@error('supplier_broker_id'){{ $message }}@enderror</span>
    </div>
    <div class="col-12 col-md-4 mb-3">
        <label class="col-form-label">Supplier <span class="text-danger">*</span></label>
        <select name="supplier_id" id="supplier_id" class="form-select search-select" @disabled(! $selectedBrokerId)>
            <option value="">-- Select Supplier --</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}"
                    data-broker-id="{{ $supplier->supplier_broker_id }}"
                    {{ old('supplier_id', $order->supplier_id ?? '') == $supplier->id ? 'selected' : '' }}>
                    {{ $supplier->name }}
                </option>
            @endforeach
        </select>
        <span class="text-danger small supplier_id_error">@error('supplier_id'){{ $message }}@enderror</span>
    </div>
    <div class="col-12 col-md-4 mb-3">
        <label class="col-form-label">Supplier Order ID</label>
        <input type="text" name="supplier_order_id" id="supplier_order_id"
               value="{{ old('supplier_order_id', $order->supplier_order_id ?? '') }}"
               class="form-control" maxlength="100" placeholder="Supplier reference / PO number">
        <span class="text-danger small supplier_order_id_error">@error('supplier_order_id'){{ $message }}@enderror</span>
    </div>
    <div class="col-12 col-md-4 mb-3">
        <label class="col-form-label">Order Date <span class="text-danger">*</span></label>
        <div class="icon-form">
            <span class="form-icon"><i class="ti ti-calendar-check"></i></span>
            <input type="text" name="order_date" id="order_date"
                   value="{{ old('order_date', isset($order) ? $order->order_date?->format('Y-m-d') : now()->format('Y-m-d')) }}"
                   class="form-control flatpickr" placeholder="DD-MM-YYYY" autocomplete="off">
        </div>
        <span class="text-danger small order_date_error">@error('order_date'){{ $message }}@enderror</span>
    </div>
    <div class="col-12 col-md-4 mb-3">
        <label class="col-form-label">Price Basis <span class="text-danger">*</span></label>
        <select name="price_basis" id="price_basis" class="form-select search-select">
            <option value="">-- Select Price Basis --</option>
            @foreach ($price_basis_options as $option)
                <option value="{{ $option }}"
                    {{ old('price_basis', $order->price_basis ?? '') === $option ? 'selected' : '' }}>
                    {{ $option }}
                </option>
            @endforeach
        </select>
        <span class="text-danger small price_basis_error">@error('price_basis'){{ $message }}@enderror</span>
    </div>
</div>
