{{-- Editable late fee. Shown only when automatic late fee is off. --}}
@php
    $lateFeePrefix = $idPrefix ?? 'dispatch';
    $lateFeeName = $name ?? 'accrued_late_fee';
    $lateFeeValue = ($useOld ?? true) ? old($lateFeeName, $value ?? '') : ($value ?? '');
    $lateFeeErrorId = ($errorId ?? ($lateFeePrefix . '_accrued_late_fee')) . '-error';
    $showManualLateFee = ! app(\App\Services\PaymentReceivableService::class)->isAutoLateFeeEnabled();
@endphp
@if ($showManualLateFee)
    <div class="col-md-12 mb-3">
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
        <small class="text-muted">Optional. Added to this dispatch. Use it to put a previous order's late fee on this order.</small>
        <small class="text-muted d-block manual-late-fee-source" id="{{ $lateFeePrefix }}_late_fee_source_label"></small>
        <span class="field-error" id="{{ $lateFeeErrorId }}"></span>
        @error($lateFeeName)
            <span class="text-danger small d-block">{{ $message }}</span>
        @enderror
    </div>
@endif
