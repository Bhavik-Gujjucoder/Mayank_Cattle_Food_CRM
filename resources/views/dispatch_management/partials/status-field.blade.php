{{-- Payment status: 0 = Unpaid (default), 1 = Paid --}}
@php
    $statusName     = $name ?? 'status';
    $statusIdPrefix = $idPrefix ?? 'dispatch';
    $statusValue    = (string) old($statusName, $value ?? '0');
@endphp
<div class="col-md-12 mb-3">
    <label class="col-form-label">
        Payment Status <span class="text-danger">*</span>
    </label>
    <div class="payment-status-group">
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="{{ $statusName }}"
                id="{{ $statusIdPrefix }}_status_unpaid" value="0"
                {{ $statusValue === '0' ? 'checked' : '' }}>
            <label class="form-check-label" for="{{ $statusIdPrefix }}_status_unpaid">Unpaid</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="{{ $statusName }}"
                id="{{ $statusIdPrefix }}_status_paid" value="1"
                {{ $statusValue === '1' ? 'checked' : '' }}>
            <label class="form-check-label" for="{{ $statusIdPrefix }}_status_paid">Paid</label>
        </div>
    </div>
    <span class="field-error" id="{{ $errorId ?? $statusName }}-error"></span>
</div>
