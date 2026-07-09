<style>
    #weeklyReportItemsTable tbody td,
    .current-day-report-module table.dashboard-recent-table tbody td {
        vertical-align: middle;
    }

    #weeklyReportItemsTable tbody tr.wr-row-confirmed > td,
    .current-day-report-module table.dashboard-recent-table tbody tr.wr-row-confirmed > td {
        background-color: #f8fafc !important;
        border-color: #e5e7eb !important;
        color: #334155;
        box-shadow: none !important;
    }

    #weeklyReportItemsTable tbody tr.wr-row-confirmed > td:first-child,
    .current-day-report-module table.dashboard-recent-table tbody tr.wr-row-confirmed > td:first-child {
        box-shadow: inset 3px 0 0 #10b981 !important;
    }

    #weeklyReportItemsTable tbody tr.wr-row-confirmed:hover > td,
    .current-day-report-module table.dashboard-recent-table tbody tr.wr-row-confirmed:hover > td {
        background-color: #f1f5f9 !important;
    }

    .wr-readonly-value {
        display: flex;
        align-items: center;
        min-height: 31px;
        padding: 0.25rem 0.5rem;
        line-height: 1.5;
        word-break: break-word;
    }

    .wr-readonly-value--note {
        align-items: flex-start;
        min-height: 52px;
        white-space: pre-wrap;
    }

    .wr-locked-action {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        min-height: 31px;
        justify-content: center;
    }
</style>
