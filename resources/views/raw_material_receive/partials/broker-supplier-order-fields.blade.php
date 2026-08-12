@php
    $selectedBrokerId = $selectedBrokerId ?? '';
    $selectedSupplierId = $selectedSupplierId ?? '';
@endphp
<div class="col-12 col-md-4 mb-3">
    <label class="col-form-label">Supplier Broker <span class="text-danger">*</span></label>
    <select id="receive_supplier_broker_id" class="form-select search-select">
        <option value="">-- Select Supplier Broker --</option>
        @foreach ($supplier_brokers as $broker)
            <option value="{{ $broker->id }}" {{ (string) $selectedBrokerId === (string) $broker->id ? 'selected' : '' }}>
                {{ $broker->name }}
            </option>
        @endforeach
    </select>
    <span class="text-danger small receive_supplier_broker_id_error"></span>
</div>
<div class="col-12 col-md-4 mb-3">
    <label class="col-form-label">Supplier <span class="text-danger">*</span></label>
    <select id="receive_supplier_id" class="form-select search-select" @disabled(! $selectedBrokerId)>
        <option value="">-- Select Supplier --</option>
            @foreach ($suppliers as $supplier)
            <option value="{{ $supplier['id'] }}"
                    data-broker-id="{{ $supplier['supplier_broker_id'] }}"
                    {{ (string) $selectedSupplierId === (string) $supplier['id'] ? 'selected' : '' }}>
                {{ $supplier['name'] }}
            </option>
            @endforeach
    </select>
    <span class="text-danger small receive_supplier_id_error"></span>
</div>
<div class="col-12 col-md-4 mb-3">
    <label class="col-form-label">Purchase Order <span class="text-danger">*</span></label>
    <select name="raw_material_order_id" id="raw_material_order_id" class="form-select search-select" @disabled(! $selectedSupplierId)>
        <option value="">-- Select Order --</option>
    </select>
    <span class="text-danger small raw_material_order_id_error">@error('raw_material_order_id'){{ $message }}@enderror</span>
</div>
