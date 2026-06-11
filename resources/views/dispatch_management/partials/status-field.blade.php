{{-- Payment status: 0 = Unpaid (default), 1 = Paid, 2 = Partial Payment --}}
@php
    $statusName      = $name ?? 'status';
    $statusIdPrefix  = $idPrefix ?? 'dispatch';
    $statusValue     = (string) old($statusName, $value ?? '0');
    $partialAmount   = old('partial_paid_amount', $partialPaidAmount ?? '');
    $showPartialWrap = $statusValue === '2';
@endphp
<div class="col-md-12 mb-3">
    <label class="col-form-label">
        Payment Status <span class="text-danger">*</span>
    </label>
    <div class="payment-status-group">
        <div class="form-check form-check-inline">
            <input class="form-check-input dispatch-payment-status-radio" type="radio"
                name="{{ $statusName }}" data-prefix="{{ $statusIdPrefix }}"
                id="{{ $statusIdPrefix }}_status_unpaid" value="0"
                {{ $statusValue === '0' ? 'checked' : '' }}>
            <label class="form-check-label" for="{{ $statusIdPrefix }}_status_unpaid">Unpaid</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input dispatch-payment-status-radio" type="radio"
                name="{{ $statusName }}" data-prefix="{{ $statusIdPrefix }}"
                id="{{ $statusIdPrefix }}_status_paid" value="1"
                {{ $statusValue === '1' ? 'checked' : '' }}>
            <label class="form-check-label" for="{{ $statusIdPrefix }}_status_paid">Paid</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input dispatch-payment-status-radio" type="radio"
                name="{{ $statusName }}" data-prefix="{{ $statusIdPrefix }}"
                id="{{ $statusIdPrefix }}_status_partial" value="2"
                {{ $statusValue === '2' ? 'checked' : '' }}>
            <label class="form-check-label" for="{{ $statusIdPrefix }}_status_partial">Partial Payment</label>
        </div>
    </div>
    <span class="field-error" id="{{ $errorId ?? $statusName }}-error"></span>

    <div class="partial-amount-wrap mt-2" id="{{ $statusIdPrefix }}_partial_amount_wrap"
        style="{{ $showPartialWrap ? '' : 'display:none;' }}">
        <label class="col-form-label" for="{{ $statusIdPrefix }}_partial_paid_amount">
            Paid Amount <span class="text-danger">*</span>
        </label>
        <div class="input-group" style="max-width:260px;">
            <span class="input-group-text">₹</span>
            <input type="number" name="partial_paid_amount" id="{{ $statusIdPrefix }}_partial_paid_amount"
                value="{{ $partialAmount }}" class="form-control" placeholder="0.00" min="0" step="0.01">
        </div>
        <span class="field-error" id="{{ $statusIdPrefix }}_partial_paid_amount-error"></span>
    </div>
</div>
