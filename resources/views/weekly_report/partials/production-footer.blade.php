<div class="weekly-report-footer wr-summary-section">
    @php
        $bagsPerHour = $report->bagsPerHour();
    @endphp
    <div class="wr-summary-head">
        <div class="wr-section-label mb-0">
            <i class="ti ti-chart-bar"></i> Production summary
        </div>
        @if ($canEdit)
            <button type="button" class="btn btn-primary btn-sm save-footer-btn">
                <i class="ti ti-device-floppy me-1"></i>Save summary
            </button>
        @endif
    </div>
    <div class="row g-3 wr-footer-grid">
        <div class="col-12 col-sm-6 col-lg-4 col-xl">
            <div class="wr-footer-card h-100">
                <label class="wr-footer-label">Total quantity (bags)</label>
                <input type="text" class="form-control wr-footer-input footer-total" readonly
                    value="{{ number_format($totalBags, 2, '.', '') }}">
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-4 col-xl">
            <div class="wr-footer-card h-100">
                <label class="wr-footer-label">Already produced / ready stock</label>
                <input type="number" class="form-control wr-footer-input footer-already-produced"
                    min="0" step="0.01" max="{{ number_format($totalBags, 2, '.', '') }}"
                    value="{{ number_format((float) $report->already_produced, 2, '.', '') }}"
                    {{ $canEdit ? '' : 'readonly' }}>
                <div class="text-danger small mt-1 footer-already-produced-error"></div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-4 col-xl">
            <div class="wr-footer-card h-100">
                <label class="wr-footer-label">Difference</label>
                <input type="text" class="form-control wr-footer-input footer-difference" readonly
                    value="{{ number_format($difference, 2, '.', '') }}">
                <span class="wr-footer-hint">Total minus ready stock</span>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-6 col-xl">
            <div class="wr-footer-card h-100">
                <label class="wr-footer-label">Bags per hour (divisor)</label>
                <input type="number" class="form-control wr-footer-input footer-bags-per-hour"
                    min="0.01" step="0.01"
                    value="{{ number_format($bagsPerHour, 2, '.', '') }}"
                    {{ $canEdit ? '' : 'readonly' }}>
                <span class="wr-footer-hint">Used as difference ÷ this value</span>
                <div class="text-danger small mt-1 footer-bags-per-hour-error"></div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-6 col-xl">
            <div class="wr-footer-card h-100">
                <label class="wr-footer-label footer-hours-label">Production hours (÷ {{ number_format($bagsPerHour, 0, '.', '') }})</label>
                <input type="number" class="form-control wr-footer-input footer-hours"
                    min="0" step="0.01"
                    value="{{ number_format($hours, 2, '.', '') }}"
                    {{ $canEdit ? '' : 'readonly' }}>
                <span class="wr-footer-hint">Auto-calculated; you may override</span>
            </div>
        </div>
    </div>
</div>
