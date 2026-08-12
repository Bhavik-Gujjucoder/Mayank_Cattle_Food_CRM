@php
    $modifier = $modifier ?? '';
    $paymentDueDaysByType = $paymentDueDaysByType ?? [
        'cash'   => (int) ($paymentDueDays ?? 0),
        'credit' => (int) ($paymentDueDays ?? 0),
    ];
    $cashDueDays = (int) ($paymentDueDaysByType['cash'] ?? 0);
    $creditDueDays = (int) ($paymentDueDaysByType['credit'] ?? 0);
@endphp
<p class="mb-1 dpp-footnote dpp-footnote-line {{ $modifier }}">
    <span class="dpp-footnote-label">Pending Payment Days:</span>
    Days count from dispatch date to current day (per unpaid or partial dispatch), shown as
    <span class="dpp-footnote-italic">days (dispatch date)</span> on print/PDF and Excel; hover a day count on screen to see dispatch date.
    @if ($cashDueDays > 0 || $creditDueDays > 0)
        Aging uses the order Payment Type —
        @if ($cashDueDays > 0)
            Cash: green ≤ {{ $cashDueDays }} days, amber up to {{ $cashDueDays + 7 }}, red beyond
        @else
            Cash: late-fee aging disabled
        @endif
        ;
        @if ($creditDueDays > 0)
            Credit: green ≤ {{ $creditDueDays }} days, amber up to {{ $creditDueDays + 7 }}, red beyond
        @else
            Credit: late-fee aging disabled
        @endif.
    @endif
</p>
<p class="mb-1 dpp-footnote dpp-footnote-line {{ $modifier }}">
    <span class="dpp-footnote-label">Late fee:</span>
    After the configured Cash or Credit due days (from order Payment Type), a daily late fee is added at midnight per unpaid/partial dispatch
    (rate × dispatched qty). Accrued fees are shown in Late Fee; Balance Due includes base amount + late fee minus any partial payment.
</p>
<p class="mb-0 dpp-footnote dpp-footnote-line {{ $modifier }}">
    <span class="dpp-footnote-label">Scope:</span>
    Only orders with at least one unpaid or partial dispatch payment are listed.
</p>
