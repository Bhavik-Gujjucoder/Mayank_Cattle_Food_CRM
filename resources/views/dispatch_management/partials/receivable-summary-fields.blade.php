@php
    $idPrefix = $idPrefix ?? 'recv';
@endphp
<div class="row g-3 receivable-summary-fields mb-0" id="{{ $idPrefix }}_receivable_section">
    <div class="col-12">
        <h6 class="mb-0 text-muted">Payment Receivable</h6>
    </div>
    <div class="col-md-4">
        <label class="col-form-label">Base Amount</label>
        <input type="text" class="form-control" id="{{ $idPrefix }}_base_amount" readonly>
    </div>
    <div class="col-md-4">
        <label class="col-form-label">Accrued Late Fee</label>
        <input type="text" class="form-control" id="{{ $idPrefix }}_accrued_late_fee" readonly>
    </div>
    <div class="col-md-4">
        <label class="col-form-label">Total Receivable</label>
        <input type="text" class="form-control fw-medium" id="{{ $idPrefix }}_total_receivable" readonly>
    </div>
    <div class="col-md-4">
        <label class="col-form-label">Overdue Days</label>
        <input type="text" class="form-control" id="{{ $idPrefix }}_overdue_days" readonly>
    </div>
    <div class="col-md-4">
        <label class="col-form-label">Balance Due</label>
        <input type="text" class="form-control fw-medium text-danger" id="{{ $idPrefix }}_balance_due" readonly>
    </div>
</div>
