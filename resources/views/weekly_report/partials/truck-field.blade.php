@php
    $name = $name ?? 'truck_number';
    $selectClass = $selectClass ?? '';
    $selected = $selected ?? null;
    $disabled = $disabled ?? true;
    $label = $label ?? 'Truck number';
@endphp
@if ($label !== '')
    <label class="col-form-label">{{ $label }}</label>
@endif
<div class="wr-truck-field">
    <select name="{{ $name }}"
        class="form-select form-select-sm {{ $selectClass }} wr-truck-select"
        {{ $disabled ? 'disabled' : '' }}
        @if ($selected) data-current="{{ $selected }}" @endif>
        <option value="">{{ $disabled ? 'Select transporter first' : '— Select —' }}</option>
        @if ($selected)
            <option value="{{ $selected }}" selected>{{ $selected }}</option>
        @endif
    </select>
    @can('add-truck')
        <div class="mt-1 wr-truck-add-link" @if ($disabled) style="display:none;" @endif>
            <a href="javascript:void(0)" class="text-primary wr-quick-add-link wr-quick-add-truck">
                <i class="ti ti-plus me-1"></i>Add Truck
            </a>
        </div>
    @endcan
</div>
