<div class="row current-day-report-module rm-daily-summary-module mb-4" id="sales_daily_sheets_card">
    <div class="col-12 d-flex">
        <div class="card flex-fill w-100 recent-cards">
            <div class="card-header pb-3">
                <div class="row align-items-center g-3 mb-0">
                    <div class="col-12 col-sm-auto me-auto">
                        <div class="d-flex align-items-center gap-2">
                            <div class="dispatch-index-icon">
                                <i class="ti ti-files"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="dispatch-index-eyebrow">Sales</div>
                                <div class="dispatch-index-title">Daily Sales Sheets</div>
                                <p class="text-muted small mb-0 mt-1">
                                    Pending orders by brand, broker and product — {{ now()->format('d M Y') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-auto">
                        <div class="comm-header-right-btn d-flex flex-wrap gap-2">
                            <a href="{{ route('dashboard.sales-daily-sheets.export') }}"
                                class="btn btn-primary btn-md" id="salesDailySheetsExcelBtn">
                                <i class="ti ti-file-spreadsheet me-1"></i>Export Excel
                            </a>
                            <a href="{{ route('dashboard.sales-daily-sheets.export-pdf') }}"
                                class="btn btn-outline-secondary btn-md" id="salesDailySheetsPdfBtn">
                                <i class="ti ti-file-type-pdf me-1"></i>Export PDF
                            </a>
                        </div>
                    </div>
                </div>
                <p class="text-muted small mb-0 mt-3 pt-3 border-top">
                    One file with three tabs/sections: <strong>Brand</strong> (all brands stacked),
                    <strong>Broker</strong> (all brokers stacked), and <strong>Product</strong> (all products stacked).
                    Same layout in Excel and PDF.
                </p>
            </div>
        </div>
    </div>
</div>
