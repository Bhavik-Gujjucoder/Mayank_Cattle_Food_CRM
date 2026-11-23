{{-- Typed late fee, plus the earlier unpaid or partial dispatch it is for. --}}
@php
    $lateFeePrefix = $idPrefix ?? 'dispatch';
    $lateFeeName = $name ?? 'accrued_late_fee';
    $lateFeeValue = ($useOld ?? true) ? old($lateFeeName, $value ?? '') : ($value ?? '');
    $lateFeeErrorId = ($errorId ?? ($lateFeePrefix . '_accrued_late_fee')) . '-error';
    $lateFeeDispatchName = $dispatchName ?? 'late_fee_dispatch_id';
    $lateFeeDispatchValue = ($useOld ?? true)
        ? old($lateFeeDispatchName, $dispatchValue ?? '')
        : ($dispatchValue ?? '');
@endphp
<div class="col-md-6 mb-3">
    <label class="col-form-label" for="{{ $lateFeePrefix }}_accrued_late_fee">Late Fee</label>
    <div class="input-group" style="max-width:260px;">
        <span class="input-group-text">₹</span>
        <input type="number"
            name="{{ $lateFeeName }}"
            id="{{ $lateFeePrefix }}_accrued_late_fee"
            value="{{ $lateFeeValue }}"
            class="form-control manual-late-fee-field"
            placeholder="0.00"
            min="0"
            step="0.01">
    </div>
    <small class="text-muted">Optional. Added to this dispatch.</small>
    <small class="text-muted d-block manual-late-fee-source" id="{{ $lateFeePrefix }}_late_fee_source_label"></small>
    <span class="field-error" id="{{ $lateFeeErrorId }}"></span>
    @error($lateFeeName)
        <span class="text-danger small d-block">{{ $message }}</span>
    @enderror
</div>
<div class="col-md-6 mb-3">
    <label class="col-form-label" for="{{ $lateFeePrefix }}_late_fee_dispatch_id">Late fee for dispatch</label>
    <select name="{{ $lateFeeDispatchName }}"
        id="{{ $lateFeePrefix }}_late_fee_dispatch_id"
        class="form-select late-fee-dispatch-select"
        data-selected="{{ $lateFeeDispatchValue }}">
        <option value="">— Select an unpaid or partial dispatch —</option>
    </select>
    <small class="text-muted">Required when a late fee is entered. Same dealer, already saved, and not this row.</small>
    <span class="field-error" id="{{ $lateFeePrefix }}_late_fee_dispatch_id-error"></span>
    @error($lateFeeDispatchName)
        <span class="text-danger small d-block">{{ $message }}</span>
    @enderror
</div>
