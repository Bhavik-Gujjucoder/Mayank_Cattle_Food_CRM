@php
    $exportQuery = $exportQuery ?? [];
    $printQuery = array_merge($exportQuery, ['auto_print' => 1]);
@endphp
<div class="wr-export-print-actions d-flex flex-wrap gap-2 {{ $class ?? '' }}">
    <a href="{{ route('weekly-report.export', $exportQuery) }}"
        class="btn btn-primary btn-sm">
        <i class="ti ti-file-export me-1"></i>Export Excel
    </a>
    <a href="{{ route('weekly-report.print', $printQuery) }}"
        class="btn btn-outline-secondary btn-sm"
        target="_blank"
        rel="noopener">
        <i class="ti ti-printer me-1"></i>Print
    </a>
</div>
