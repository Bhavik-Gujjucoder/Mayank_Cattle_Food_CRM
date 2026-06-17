@php
    $modifier = $modifier ?? '';
    $paymentDueDays = (int) ($paymentDueDays ?? 0);
@endphp
<p class="mb-1 dpp-footnote dpp-footnote-line {{ $modifier }}">
    <span class="dpp-footnote-label">Pending Payment Days:</span>
    Days count from dispatch date to current day (per unpaid or partial dispatch), shown as
    <span class="dpp-footnote-italic">days (dispatch date)</span> on print/PDF and Excel; hover a day count on screen to see dispatch date.
    @if ($paymentDueDays > 0)
        Green ≤ {{ $paymentDueDays }} days (within due period), amber up to {{ $paymentDueDays + 7 }} days overdue, red beyond that.
    @endif
</p>
<p class="mb-1 dpp-footnote dpp-footnote-line {{ $modifier }}">
    <span class="dpp-footnote-label">Late fee:</span>
    After the configured payment due days, a daily late fee is added at midnight per unpaid/partial dispatch
    (rate × dispatched qty). Accrued fees are shown in Late Fee; Balance Due includes base amount + late fee minus any partial payment.
</p>
<p class="mb-0 dpp-footnote dpp-footnote-line {{ $modifier }}">
    <span class="dpp-footnote-label">Scope:</span>
    Only orders with at least one unpaid or partial dispatch payment are listed.
</p>
