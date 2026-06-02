<style>
    /*
     * Dispatch Pending Payments — aligned with CRM list tables (Order / Dispatch)
     * App standard: .card > .custom-table > .table.thead-light, row borders #E8E8E8
     */

    .delivery-pending-payments-module {
        --dpp-line: #e8e8e8;
        --dpp-head-bg: #f1f5f9;
    }

    /* ── Main card (same pattern as order_management index) ── */
    .delivery-pending-payments-module .dpp-main-card {
        margin-bottom: 0;
    }

    .delivery-pending-payments-module .dpp-header-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: stretch;
        justify-content: flex-end;
    }

    .delivery-pending-payments-module .dpp-header-actions .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem 1.15rem;
        min-height: 42px;
        font-size: 0.9375rem;
        font-weight: 500;
        white-space: nowrap;
    }

    .delivery-pending-payments-module .dpp-btn-label-short {
        display: none;
    }

    .delivery-pending-payments-module .dispatch-index-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(var(--bs-primary-rgb), 0.1);
        color: var(--bs-primary);
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .delivery-pending-payments-module .dpp-brand-section {
        margin-bottom: 1.5rem;
    }

    .delivery-pending-payments-module .dpp-brand-section:last-child {
        margin-bottom: 0;
    }

    .delivery-pending-payments-module .dpp-brand-section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .delivery-pending-payments-module .dpp-brand-section-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #262a2a;
    }

    .delivery-pending-payments-module .dpp-footnotes {
        border-color: var(--dpp-line) !important;
    }

    .delivery-pending-payments-module .dpp-footnote {
        font-weight: 400;
        font-synthesis: none;
    }

    .delivery-pending-payments-module .dpp-footnote-label {
        font-weight: 600;
    }

    .delivery-pending-payments-module .dpp-footnote-italic {
        font-style: italic;
        font-weight: 400;
    }

    /* ── Tables: match global .custom-table / .dataTable row lines ── */
    .delivery-pending-payments-module .custom-table .dpp-report-table {
        margin-bottom: 0;
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .delivery-pending-payments-module .custom-table .dpp-report-table thead.thead-light th {
        background-color: var(--dpp-head-bg) !important;
        color: #262a2a;
        font-size: 14px;
        font-weight: 500;
        border: none;
        border-bottom: 1px solid var(--dpp-line);
        white-space: nowrap;
        vertical-align: middle;
    }

    .delivery-pending-payments-module .custom-table .dpp-report-table tbody tr {
        border-color: var(--dpp-line);
    }

    .delivery-pending-payments-module .custom-table .dpp-report-table tbody td {
        color: #6f6f6f;
        font-size: 14px;
        border: none;
        border-bottom: 1px solid var(--dpp-line);
        vertical-align: middle;
        padding: 0.75rem;
    }

    .delivery-pending-payments-module .custom-table .dpp-report-table tbody tr:last-child td {
        border-bottom: none;
    }

    .delivery-pending-payments-module .custom-table .dpp-report-table .dpp-col-city {
        width: 12%;
    }

    .delivery-pending-payments-module .custom-table .dpp-report-table .dpp-col-dealer {
        width: 22%;
    }

    .delivery-pending-payments-module .custom-table .dpp-report-table .dpp-col-order {
        width: 22%;
    }

    .delivery-pending-payments-module .custom-table .dpp-report-table .dpp-col-days {
        width: 44%;
    }

    .delivery-pending-payments-module .custom-table .dpp-report-table .dpp-days-col {
        word-break: break-word;
        overflow-wrap: anywhere;
        vertical-align: middle;
    }

    /* ── Screen desktop: pending day pills ── */
    .delivery-pending-payments-module .dpp-days-print {
        display: none !important;
    }

    .delivery-pending-payments-module .dpp-days-screen .dpp-day-pill {
        cursor: help;
        border-bottom: 1px dotted currentColor;
        display: inline-block;
        padding: 0.1rem 0.3rem;
        border-radius: 4px;
        line-height: 1.3;
    }

    .delivery-pending-payments-module .dpp-days-screen .dpp-day-pill--low {
        color: #15803d;
        background: #f0fdf4;
        border-bottom-color: #86efac;
    }

    .delivery-pending-payments-module .dpp-days-screen .dpp-day-pill--mid {
        color: #b45309;
        background: #fffbeb;
        border-bottom-color: #fcd34d;
    }

    .delivery-pending-payments-module .dpp-days-screen .dpp-day-pill--high {
        color: #b91c1c;
        background: #fef2f2;
        border-bottom-color: #fca5a5;
    }

    .delivery-pending-payments-module .dpp-days-screen .dpp-days-sep {
        pointer-events: none;
        color: #94a3b8;
    }

    @media (max-width: 575.98px) {
        .delivery-pending-payments-module .dpp-btn-label-long {
            display: none;
        }

        .delivery-pending-payments-module .dpp-btn-label-short {
            display: inline;
        }
    }

    @media (max-width: 991.98px) {
        .delivery-pending-payments-module .dpp-header-actions {
            justify-content: flex-start;
            width: 100%;
        }

        .delivery-pending-payments-module .dpp-header-filters .form-select,
        .delivery-pending-payments-module .dpp-header-filters .select2-container {
            width: 100% !important;
        }

        .delivery-pending-payments-module .custom-table .dpp-report-table {
            min-width: 640px;
        }
    }

    /* ── Mobile: order cards + chips ── */
    @media (max-width: 767.98px) {
        .delivery-pending-payments-module .dpp-main-card .card-header {
            padding: 0.75rem;
        }

        .delivery-pending-payments-module .dpp-header-icon {
            width: 36px;
            height: 36px;
            font-size: 1rem;
        }

        .delivery-pending-payments-module .dispatch-index-title {
            font-size: 1rem;
            line-height: 1.25;
        }

        .delivery-pending-payments-module .dispatch-index-eyebrow {
            font-size: 0.65rem;
        }

        .delivery-pending-payments-module .dpp-header-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            width: 100%;
        }

        .delivery-pending-payments-module .dpp-header-actions .btn {
            width: 100%;
            min-height: 40px;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }

        .delivery-pending-payments-module .dpp-brand-section-title {
            font-size: 0.875rem;
        }

        .delivery-pending-payments-module .dpp-mobile-list {
            padding: 0;
        }

        .delivery-pending-payments-module .dpp-mobile-order {
            background: #fff;
            border: 1px solid var(--dpp-line);
            border-radius: 6px;
            padding: 0.65rem 0.75rem;
            margin-bottom: 0.5rem;
        }

        .delivery-pending-payments-module .dpp-mobile-order:last-child {
            margin-bottom: 0;
        }

        .delivery-pending-payments-module .dpp-mobile-order-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .delivery-pending-payments-module .dpp-mobile-order-main {
            min-width: 0;
            flex: 1;
        }

        .delivery-pending-payments-module .dpp-mobile-order-id {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #262a2a;
            line-height: 1.3;
            word-break: break-word;
        }

        .delivery-pending-payments-module a.dpp-mobile-order-id {
            color: var(--bs-primary);
            text-decoration: none;
        }

        .delivery-pending-payments-module .dpp-mobile-order-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.2rem 0.35rem;
            margin-top: 0.2rem;
            font-size: 0.75rem;
            color: #64748b;
        }

        .delivery-pending-payments-module .dpp-mobile-meta-item i {
            font-size: 0.7rem;
            margin-right: 0.15rem;
            vertical-align: -1px;
        }

        .delivery-pending-payments-module .dpp-mobile-meta-sep {
            color: #cbd5e1;
        }

        .delivery-pending-payments-module .dpp-mobile-max-badge {
            flex-shrink: 0;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.2rem 0.45rem;
            border-radius: 6px;
            background: #f8fafc;
            border: 1px solid var(--dpp-line);
            line-height: 1;
        }

        .delivery-pending-payments-module .dpp-mobile-order-days {
            border-top: 1px dashed var(--dpp-line);
            padding-top: 0.5rem;
        }

        .delivery-pending-payments-module .dpp-mobile-days-title {
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #94a3b8;
            margin-bottom: 0.4rem;
        }

        .delivery-pending-payments-module .dpp-day-chips--mobile {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
        }

        .delivery-pending-payments-module .dpp-day-chips--mobile .dpp-day-chip {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 3.25rem;
            padding: 0.25rem 0.4rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            line-height: 1.15;
            text-align: center;
            cursor: help;
        }

        .delivery-pending-payments-module .dpp-day-chips--mobile .dpp-day-chip-num {
            font-size: 0.875rem;
            font-weight: 700;
        }

        .delivery-pending-payments-module .dpp-day-chips--mobile .dpp-day-chip-date {
            font-size: 0.6rem;
            color: #64748b;
            white-space: nowrap;
        }

        .delivery-pending-payments-module .dpp-day-chips--mobile .dpp-day-chip--low {
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        .delivery-pending-payments-module .dpp-day-chips--mobile .dpp-day-chip--low .dpp-day-chip-num {
            color: #15803d;
        }

        .delivery-pending-payments-module .dpp-day-chips--mobile .dpp-day-chip--mid {
            border-color: #fde68a;
            background: #fffbeb;
        }

        .delivery-pending-payments-module .dpp-day-chips--mobile .dpp-day-chip--mid .dpp-day-chip-num {
            color: #b45309;
        }

        .delivery-pending-payments-module .dpp-day-chips--mobile .dpp-day-chip--high {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .delivery-pending-payments-module .dpp-day-chips--mobile .dpp-day-chip--high .dpp-day-chip-num {
            color: #b91c1c;
        }
    }

    /* ── Print / PDF ── */
    @media print {
        .main-wrapper .mobile-user-menu {
            display: none !important;
        }

        .delivery-pending-payments-module .dpp-header-filters,
        .delivery-pending-payments-module .dpp-header-actions {
            display: none !important;
        }

        .delivery-pending-payments-module .dpp-mobile-list {
            display: none !important;
        }

        .delivery-pending-payments-module .dpp-footnote--mobile {
            display: none !important;
        }

        .delivery-pending-payments-module .dpp-footnote-line.d-print-block {
            display: block !important;
        }

        .delivery-pending-payments-module .dpp-table-wrap.d-none {
            display: block !important;
            overflow: visible !important;
        }

        .delivery-pending-payments-module .dpp-main-card {
            border: 1px solid var(--dpp-line) !important;
            box-shadow: none !important;
        }

        .delivery-pending-payments-module .dpp-main-card .card-header {
            border-bottom: 1px solid var(--dpp-line) !important;
        }

        .delivery-pending-payments-module .dpp-brand-section {
            page-break-inside: avoid;
            margin-bottom: 1rem !important;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--dpp-line);
        }

        .delivery-pending-payments-module .dpp-brand-section:last-of-type {
            border-bottom: none;
            margin-bottom: 0 !important;
            padding-bottom: 0;
        }

        .delivery-pending-payments-module .custom-table .dpp-report-table {
            table-layout: fixed !important;
            width: 100% !important;
        }

        .delivery-pending-payments-module .custom-table .dpp-report-table thead.thead-light th {
            background-color: var(--dpp-head-bg) !important;
            font-size: 9px !important;
            font-weight: 600 !important;
            padding: 5px 6px !important;
            border: none !important;
            border-bottom: 1px solid var(--dpp-line) !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .delivery-pending-payments-module .custom-table .dpp-report-table tbody td {
            font-size: 9px !important;
            padding: 5px 6px !important;
            border: none !important;
            border-bottom: 1px solid var(--dpp-line) !important;
            vertical-align: top !important;
            overflow: visible !important;
            height: auto !important;
        }

        .delivery-pending-payments-module .custom-table .dpp-report-table tbody tr:last-child td {
            border-bottom: none !important;
        }

        .delivery-pending-payments-module .custom-table .dpp-report-table .dpp-col-city {
            width: 11% !important;
        }

        .delivery-pending-payments-module .custom-table .dpp-report-table .dpp-col-dealer {
            width: 20% !important;
        }

        .delivery-pending-payments-module .custom-table .dpp-report-table .dpp-col-order {
            width: 19% !important;
        }

        .delivery-pending-payments-module .custom-table .dpp-report-table .dpp-col-days {
            width: 50% !important;
        }

        .delivery-pending-payments-module .dpp-days-screen {
            display: none !important;
        }

        .delivery-pending-payments-module .dpp-days-print {
            display: block !important;
            font-weight: 400 !important;
        }

        .delivery-pending-payments-module .dpp-days-print .dpp-day-chips--print {
            display: grid !important;
            grid-template-columns: repeat(auto-fill, minmax(40px, max-content));
            row-gap: 6px !important;
            column-gap: 5px !important;
        }

        .delivery-pending-payments-module .dpp-days-print .dpp-day-chips--print .dpp-day-chip {
            display: inline-flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 40px;
            min-height: 30px;
            padding: 3px 5px 4px !important;
            border-radius: 4px !important;
            border-width: 1px !important;
            border-style: solid !important;
            line-height: 1.2 !important;
            break-inside: avoid;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .delivery-pending-payments-module .dpp-days-print .dpp-day-chips--print .dpp-day-chip-num {
            font-size: 9px !important;
            font-weight: 700 !important;
            display: block !important;
            margin-bottom: 2px !important;
        }

        .delivery-pending-payments-module .dpp-days-print .dpp-day-chips--print .dpp-day-chip-date {
            font-size: 6.5px !important;
            font-weight: 400 !important;
            color: #64748b !important;
            display: block !important;
        }

        .delivery-pending-payments-module .dpp-days-print .dpp-day-chips--print .dpp-day-chip--low {
            border: 1px solid #86efac !important;
            background: #f0fdf4 !important;
        }

        .delivery-pending-payments-module .dpp-days-print .dpp-day-chips--print .dpp-day-chip--low .dpp-day-chip-num {
            color: #15803d !important;
        }

        .delivery-pending-payments-module .dpp-days-print .dpp-day-chips--print .dpp-day-chip--mid {
            border: 1px solid #fcd34d !important;
            background: #fffbeb !important;
        }

        .delivery-pending-payments-module .dpp-days-print .dpp-day-chips--print .dpp-day-chip--mid .dpp-day-chip-num {
            color: #b45309 !important;
        }

        .delivery-pending-payments-module .dpp-days-print .dpp-day-chips--print .dpp-day-chip--high {
            border: 1px solid #fca5a5 !important;
            background: #fef2f2 !important;
        }

        .delivery-pending-payments-module .dpp-days-print .dpp-day-chips--print .dpp-day-chip--high .dpp-day-chip-num {
            color: #b91c1c !important;
        }

        .delivery-pending-payments-module .dpp-footnotes {
            border-top: 1px solid var(--dpp-line) !important;
            page-break-inside: avoid;
        }

        .delivery-pending-payments-module .dpp-footnotes,
        .delivery-pending-payments-module .dpp-footnotes .dpp-footnote-line {
            font-family: Arial, Helvetica, sans-serif !important;
            font-size: 9px !important;
            font-weight: 400 !important;
            font-synthesis: none !important;
        }

        .delivery-pending-payments-module .dpp-footnotes .dpp-footnote-label {
            font-weight: 600 !important;
        }
    }
</style>
