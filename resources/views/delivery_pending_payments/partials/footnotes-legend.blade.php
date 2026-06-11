@php
    $modifier = $modifier ?? '';
@endphp
<p class="mb-1 dpp-footnote dpp-footnote-line {{ $modifier }}">
    <span class="dpp-footnote-label">Pending Payment Days:</span>
    Days count from dispatch date to current day (per unpaid or partial dispatch), shown as
    <span class="dpp-footnote-italic">days (dispatch date)</span> on print/PDF and Excel; hover a day count on screen to see dispatch date.
</p>
<p class="mb-0 dpp-footnote dpp-footnote-line {{ $modifier }}">
    <span class="dpp-footnote-label">Scope:</span>
    Only orders with at least one unpaid or partial dispatch payment are listed.
</p>
