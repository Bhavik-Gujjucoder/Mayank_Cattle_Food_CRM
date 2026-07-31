<style>
    .weekly-report-module .weekly-report-items-table tbody td {
        vertical-align: middle;
    }

    /* ── Pending rows ── */
    .weekly-report-module .weekly-report-items-table tbody tr.wr-row-pending > td {
        background-color: #fff;
        border-color: #E8E8E8 !important;
    }

    .weekly-report-module .weekly-report-items-table tbody tr.wr-row-pending:nth-child(even) > td {
        background-color: #f8fafc;
    }

    .weekly-report-module .weekly-report-items-table tbody tr.wr-row-pending > td:first-child {
        box-shadow: inset 3px 0 0 #3e6eaf;
    }

    .weekly-report-module .weekly-report-items-table tbody tr.wr-row-pending:hover > td {
        background-color: rgba(62, 110, 175, 0.06) !important;
    }

    /* ── Confirmed / complete rows ── */
    .weekly-report-module .weekly-report-items-table tbody tr.wr-row-confirmed > td {
        background-color: #ecfdf5 !important;
        border-color: #bbf7d0 !important;
        color: #14532d;
        vertical-align: middle !important;
    }

    .weekly-report-module .weekly-report-items-table tbody tr.wr-row-confirmed:hover > td {
        background-color: #d1fae5 !important;
    }

    .weekly-report-module .weekly-report-items-table tbody tr.wr-row-confirmed > td:first-child {
        box-shadow: inset 4px 0 0 #10b981 !important;
    }

    .weekly-report-module .wr-confirmed-pill {
        display: inline-flex;
        align-items: center;
        font-size: 11px;
        font-weight: 600;
        color: #047857;
        background: #d1fae5;
        border: 1px solid #6ee7b7;
        border-radius: 999px;
        padding: 0.25rem 0.6rem;
        white-space: nowrap;
    }

    .weekly-report-module .wr-dispatch-btn {
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
        border-color: #6ee7b7;
        color: #047857;
        background: #fff;
    }

    .weekly-report-module .wr-dispatch-btn:hover {
        background: #d1fae5;
        border-color: #10b981;
        color: #065f46;
    }

    .weekly-report-module .wr-readonly-value {
        display: block;
        min-height: auto;
        padding: 0.2rem 0.1rem;
        line-height: 1.45;
        word-break: break-word;
        overflow-wrap: anywhere;
        white-space: normal;
        color: #262A2A;
        font-size: 12px;
    }

    .weekly-report-module .wr-readonly-value--center {
        text-align: center;
        font-weight: 600;
    }

    .weekly-report-module tr.wr-row-confirmed .wr-readonly-value {
        color: #166534;
        font-weight: 500;
    }

    .weekly-report-module tr.wr-row-confirmed .wr-cell-order {
        color: #047857 !important;
        font-weight: 600;
    }

    .weekly-report-module .wr-readonly-value--note {
        white-space: pre-wrap;
        min-height: auto;
    }

    .weekly-report-module tr.wr-row-confirmed .wr-readonly-value--note {
        color: #15803d;
    }

    .weekly-report-module .wr-confirmed-sort-order {
        max-width: 56px;
        margin: 0 auto;
        font-weight: 600;
        color: #047857;
        background: rgba(255, 255, 255, 0.7);
        border-color: #86efac;
    }

    .weekly-report-module .wr-confirmed-sort-order:focus {
        background: #fff;
        border-color: #10b981;
        box-shadow: 0 0 0 0.15rem rgba(16, 185, 129, 0.15);
    }

    .weekly-report-module .wr-locked-action {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.35rem;
    }

    .weekly-report-module tr.wr-row-confirmed .badge.bg-success-light {
        background-color: #d1fae5 !important;
        color: #047857 !important;
        border: 1px solid #6ee7b7;
        font-weight: 600;
    }

    /* ── Scrolling table: all screen sizes ── */
    @media (max-width: 767.98px) {
        .weekly-report-module .wr-row-actions {
            flex-wrap: wrap;
            min-width: 0;
        }

        .weekly-report-module .wr-row-actions .btn {
            padding: 0.28rem 0.45rem;
        }
    }
</style>
