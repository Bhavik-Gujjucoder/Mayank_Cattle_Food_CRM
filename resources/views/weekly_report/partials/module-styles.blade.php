<style>
    /* ── Weekly Report module (compact, theme #3e6eaf) ─────────────── */
    .weekly-report-module {
        max-width: 100%;
        min-width: 0;
    }

    .weekly-report-module.card,
    .current-day-report-module.weekly-report-module .card {
        overflow: hidden;
    }

    .weekly-report-module .wr-day-section {
        background: #fff;
        border: 1px solid #E8E8E8;
        border-radius: 8px;
        margin-bottom: 0.75rem;
        overflow: hidden;
    }

    .weekly-report-module .wr-day-section:last-child {
        margin-bottom: 0;
    }

    .weekly-report-module .wr-day-header {
        background: linear-gradient(90deg, rgba(62, 110, 175, 0.08) 0%, #fff 60%);
        border-bottom: 1px solid #E8E8E8;
        padding: 0.55rem 0.85rem;
    }

    .weekly-report-module .wr-day-date {
        font-size: 0.95rem;
        font-weight: 700;
        color: #3e6eaf;
        line-height: 1.2;
        margin: 0;
    }

    .weekly-report-module .wr-day-hint {
        font-size: 12px;
        color: #6F6F6F;
        margin: 0.15rem 0 0;
        line-height: 1.3;
    }

    .weekly-report-module .wr-day-body {
        padding: 0.65rem 0.85rem 0.75rem;
    }

    .weekly-report-module .wr-section-label {
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #8a9bc0;
        margin-bottom: 0.4rem;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .weekly-report-module .wr-section-label i {
        font-size: 0.9rem;
        color: #3e6eaf;
    }

    .weekly-report-module .wr-section-count {
        font-size: inherit;
        font-weight: inherit;
        letter-spacing: inherit;
        text-transform: inherit;
        color: #3e6eaf;
        margin-left: 0.15rem;
    }

    /* Add form */
    .weekly-report-module .wr-add-panel {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 0.55rem 0.65rem 0.65rem;
        margin-bottom: 0.55rem;
    }

    .weekly-report-module .wr-add-panel .col-form-label {
        font-size: 12px;
        font-weight: 500;
        color: #262A2A;
        margin-bottom: 0.15rem;
        padding-bottom: 0;
    }

    .weekly-report-module .wr-add-panel .form-control,
    .weekly-report-module .wr-add-panel .form-select {
        font-size: 12px;
        min-height: 32px;
        padding: 0.25rem 0.5rem;
    }

    .weekly-report-module .wr-add-panel .wr-add-quantity,
    .weekly-report-module .wr-add-panel .wr-add-contact {
        min-height: 32px;
        height: 32px;
        padding-top: 0.25rem;
        padding-bottom: 0.25rem;
    }

    .weekly-report-module .wr-add-panel textarea.form-control.wr-add-note {
        min-height: 32px;
        height: 32px;
        resize: vertical;
        max-height: 72px;
        padding-top: 0.35rem;
        line-height: 1.3;
    }

    .weekly-report-module .wr-add-panel .wr-add-qty-col {
        min-width: 72px;
    }

    .weekly-report-module .wr-add-panel .wr-add-submit-col {
        padding-bottom: 0;
    }

    .weekly-report-module .wr-add-panel .wr-add-submit-btn {
        width: 100%;
        white-space: nowrap;
        min-height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    @media (min-width: 992px) {
        .weekly-report-module .wr-add-panel .wr-add-form-grid {
            align-items: flex-start;
        }

        .weekly-report-module .wr-add-panel .wr-add-submit-col {
            align-self: flex-end;
        }
    }

    .weekly-report-module .wr-transport-field .mt-1,
    .weekly-report-module .wr-truck-field .mt-1 {
        line-height: 1.2;
        margin-top: 0.2rem !important;
    }

    .weekly-report-module .wr-quick-add-link {
        font-size: 11px !important;
        font-weight: 600;
        line-height: 1.2;
        display: inline-block;
        text-decoration: none;
    }

    .weekly-report-module .wr-quick-add-link i {
        font-size: 11px;
        vertical-align: -1px;
    }

    .weekly-report-module .wr-quick-add-link:hover {
        text-decoration: underline;
    }

    /* Listing */
    .weekly-report-module .wr-listing-wrap {
        margin-bottom: 0.55rem;
        max-width: 100%;
        min-width: 0;
    }

    .weekly-report-module .wr-scroll-hint {
        font-size: 11px;
        color: #8a9bc0;
        padding: 0.25rem 0.5rem 0.35rem;
        align-items: center;
        gap: 0.25rem;
    }

    .weekly-report-module .wr-table-scroll {
        margin-bottom: 0;
        max-width: 100%;
        min-width: 0;
    }

    .weekly-report-module .wr-table-wrap {
        border: 1px solid #E8E8E8;
        border-radius: 6px;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        background: #fff;
        max-width: 100%;
        scrollbar-width: thin;
    }

    .weekly-report-module .wr-table-wrap::-webkit-scrollbar {
        height: 8px;
    }

    .weekly-report-module .wr-table-wrap::-webkit-scrollbar-thumb {
        background: rgba(62, 110, 175, 0.35);
        border-radius: 4px;
    }

    .weekly-report-module .weekly-report-items-table {
        margin-bottom: 0 !important;
        border: none !important;
        min-width: 1720px;
        width: 100%;
        table-layout: fixed;
    }

    .weekly-report-module .weekly-report-items-table col.wr-col-order-no { width: 72px; }
    .weekly-report-module .weekly-report-items-table col.wr-col-order-id { width: 148px; }
    .weekly-report-module .weekly-report-items-table col.wr-col-product { width: 200px; }
    .weekly-report-module .weekly-report-items-table col.wr-col-dealer { width: 200px; }
    .weekly-report-module .weekly-report-items-table col.wr-col-city { width: 140px; }
    .weekly-report-module .weekly-report-items-table col.wr-col-qty { width: 96px; }
    .weekly-report-module .weekly-report-items-table col.wr-col-transport { width: 168px; }
    .weekly-report-module .weekly-report-items-table col.wr-col-truck { width: 136px; }
    .weekly-report-module .weekly-report-items-table col.wr-col-contact { width: 118px; }
    .weekly-report-module .weekly-report-items-table col.wr-col-note { width: 210px; }
    .weekly-report-module .weekly-report-items-table col.wr-col-status { width: 92px; }
    .weekly-report-module .weekly-report-items-table col.wr-col-action { width: 220px; }

    .weekly-report-module .weekly-report-items-table thead th {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #3e6eaf;
        background: linear-gradient(180deg, #f0f4fa 0%, #f8f9fa 100%);
        border-bottom: 2px solid #dbeafe !important;
        border-top: none !important;
        padding: 8px 10px;
        white-space: nowrap;
        vertical-align: middle;
        position: sticky;
        top: 0;
        z-index: 2;
    }

    .weekly-report-module .weekly-report-items-table tbody td {
        font-size: 12px;
        color: #475569;
        border-color: #E8E8E8 !important;
        padding: 8px 10px;
        vertical-align: top;
        white-space: normal;
        overflow: visible;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .weekly-report-module .weekly-report-items-table tbody td.wr-cell-action,
    .weekly-report-module .weekly-report-items-table tbody td.wr-cell-status {
        white-space: nowrap;
    }

    .weekly-report-module .weekly-report-items-table .wr-readonly-value {
        line-height: 1.45;
        word-break: break-word;
        overflow-wrap: anywhere;
        white-space: normal;
    }

    .weekly-report-module .weekly-report-items-table .form-control,
    .weekly-report-module .weekly-report-items-table .form-select {
        font-size: 12px;
        min-height: 32px;
        padding: 0.3rem 0.5rem;
        border-color: #cbd5e1;
        width: 100%;
        max-width: 100%;
    }

    .weekly-report-module .weekly-report-items-table .item-sort-order {
        min-width: 52px;
        padding-left: 0.35rem;
        padding-right: 0.35rem;
    }

    .weekly-report-module .weekly-report-items-table .wr-transport-field,
    .weekly-report-module .weekly-report-items-table .wr-truck-field {
        min-width: 0;
    }

    .weekly-report-module .weekly-report-items-table .wr-transport-field .form-select,
    .weekly-report-module .weekly-report-items-table .wr-truck-field .form-select {
        min-width: 100%;
    }

    .weekly-report-module .weekly-report-items-table .form-control:focus,
    .weekly-report-module .weekly-report-items-table .form-select:focus {
        border-color: #3e6eaf;
        box-shadow: 0 0 0 0.15rem rgba(62, 110, 175, 0.15);
    }

    .weekly-report-module .weekly-report-items-table textarea.item-note {
        width: 100%;
        min-width: 100%;
        min-height: 52px;
        max-height: 80px;
        resize: vertical;
        line-height: 1.4;
    }

    .weekly-report-module .weekly-report-items-table .item-contact {
        min-width: 100%;
    }

    .weekly-report-module .weekly-report-items-table td.wr-cell-action {
        vertical-align: middle;
    }

    .weekly-report-module .weekly-report-items-table td.wr-cell-transport,
    .weekly-report-module .weekly-report-items-table td.wr-cell-truck {
        vertical-align: top;
    }

    .weekly-report-module .wr-unit-hint {
        font-size: 10px;
    }

    .weekly-report-module .wr-cell-order {
        color: #3e6eaf !important;
        font-weight: 600;
    }

    .weekly-report-module .wr-row-actions {
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        gap: 0.35rem;
        align-items: center;
        min-width: 200px;
    }

    .weekly-report-module .wr-row-actions .btn {
        font-size: 11px;
        padding: 0.3rem 0.55rem;
        line-height: 1.2;
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        flex-shrink: 0;
    }

    .weekly-report-module .wr-row-actions .btn span {
        white-space: nowrap;
    }

    .weekly-report-module .wr-row-actions form {
        display: inline;
    }

    /* Production summary */
    .weekly-report-module .wr-summary-section {
        border-top: 1px solid #E8E8E8;
        padding-top: 0.55rem;
        margin-top: 0.15rem;
    }

    .weekly-report-module .wr-summary-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.4rem;
        margin-bottom: 0.45rem;
    }

    .weekly-report-module .wr-footer-card {
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 0.45rem 0.6rem;
        height: 100%;
        background: #fff;
    }

    .weekly-report-module .wr-footer-label {
        font-size: 11px;
        color: #64748b;
        font-weight: 500;
        margin-bottom: 0.2rem;
        line-height: 1.25;
    }

    .weekly-report-module .wr-footer-input {
        font-size: 0.95rem !important;
        font-weight: 700 !important;
        color: #262A2A !important;
        border-color: #e2e8f0;
        padding: 0.25rem 0.45rem;
        min-height: 34px;
    }

    .weekly-report-module .wr-footer-input[readonly] {
        background: #f8fafc;
    }

    .weekly-report-module .wr-footer-hint {
        font-size: 10px;
        color: #94a3b8;
        margin-top: 0.1rem;
    }

    /* Date navigation — always horizontal */
    .weekly-report-module .wr-date-nav {
        display: inline-flex;
        flex-direction: row;
        flex-wrap: nowrap;
        align-items: center;
        gap: 0.35rem;
    }

    .weekly-report-module .wr-date-nav .btn {
        white-space: nowrap;
        padding: 0.35rem 0.65rem;
        font-size: 13px;
    }

    .weekly-report-module .wr-date-nav .form-control,
    .weekly-report-module .wr-date-nav .flatpickr-input {
        width: 118px !important;
        min-width: 118px;
        flex: 0 0 118px;
        text-align: center;
        font-weight: 600;
        font-size: 13px;
        color: #3e6eaf;
        padding: 0.35rem 0.4rem;
    }

    .weekly-report-module .wr-header-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 0.5rem;
    }

    .weekly-report-module .wr-empty-state {
        text-align: center;
        padding: 1rem;
        color: #94a3b8;
        font-size: 13px;
    }

    .weekly-report-module .wr-empty-state i {
        font-size: 1.5rem;
        display: block;
        margin-bottom: 0.35rem;
        color: #cbd5e1;
    }

    .weekly-report-module.card > .card-body {
        padding: 0.65rem 0.85rem 0.85rem;
    }

    .weekly-report-module.card > .card-header {
        padding-bottom: 0.5rem;
    }

    /* Filter toolbar */
    .weekly-report-module .wr-filter-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 10px;
        width: 100%;
    }

    .weekly-report-module .wr-filter-bar .common-hed-form.cls-form-select-input {
        flex: 1 1 200px;
        min-width: 180px;
        max-width: 280px;
    }

    .weekly-report-module .wr-filter-actions {
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        align-items: center;
        gap: 0.5rem;
        flex: 0 0 auto;
        padding-bottom: 0;
    }

    .weekly-report-module .wr-filter-actions .btn {
        min-height: 38px;
        padding: 0.35rem 0.85rem;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
    }

    .weekly-report-module .wr-filter-actions .btn-primary,
    .weekly-report-module .wr-filter-actions .btn-primary:hover,
    .weekly-report-module .wr-filter-actions .btn-primary:focus {
        color: #fff !important;
    }

    .weekly-report-module .wr-filter-actions .btn-danger,
    .weekly-report-module .wr-filter-actions .btn-danger:hover,
    .weekly-report-module .wr-filter-actions .btn-danger:focus {
        color: #fff !important;
    }

    .weekly-report-module .wr-filter-actions .btn i {
        color: inherit;
    }

    .weekly-report-module .wr-filter-bar .icon-form {
        width: 100%;
    }

    @media (min-width: 768px) {
        .weekly-report-module .wr-filter-bar {
            flex-wrap: nowrap;
        }
    }

    .weekly-report-module .wr-header-toolbar {
        width: 100%;
    }

    @media (max-width: 1199.98px) {
        .weekly-report-module .wr-date-nav {
            width: 100%;
            justify-content: center;
        }

        .weekly-report-module .wr-header-toolbar {
            justify-content: center;
        }

        .weekly-report-module .wr-add-panel .row > [class*="col-"] {
            margin-bottom: 0.15rem;
        }

        .weekly-report-module .wr-footer-card {
            margin-bottom: 0;
        }

        .weekly-report-module.card > .card-body,
        .current-day-report-module.weekly-report-module .card-body {
            padding-left: 0.65rem;
            padding-right: 0.65rem;
        }

        .weekly-report-module .wr-day-body {
            padding-left: 0.65rem;
            padding-right: 0.65rem;
        }
    }

    @media (max-width: 767.98px) {
        .weekly-report-module .wr-scroll-hint {
            font-size: 10px;
            padding: 0.3rem 0.45rem 0.4rem;
        }

        .weekly-report-module .weekly-report-items-table thead th {
            font-size: 10px;
            padding: 6px 8px;
        }

        .weekly-report-module .weekly-report-items-table tbody td {
            font-size: 11px;
            padding: 6px 8px;
        }

        .weekly-report-module .wr-filter-bar {
            flex-direction: column;
            align-items: stretch;
        }

        .weekly-report-module .wr-filter-bar .common-hed-form.cls-form-select-input {
            width: 100%;
            max-width: none;
            flex: 1 1 100%;
        }

        .weekly-report-module .wr-filter-actions {
            width: 100%;
        }

        .weekly-report-module .wr-filter-actions .btn {
            flex: 1;
        }

        .weekly-report-module .dispatch-index-title {
            font-size: 1.05rem;
        }

        .weekly-report-module .wr-day-date {
            font-size: 0.88rem;
        }
    }

    @media (max-width: 575.98px) {
        .weekly-report-module .wr-add-panel .row > [class*="col-"] {
            flex: 0 0 100%;
            max-width: 100%;
        }

        .weekly-report-module .wr-add-panel .wr-add-submit-btn {
            width: 100%;
        }

        .weekly-report-module .wr-summary-head {
            flex-direction: column;
            align-items: stretch;
        }

        .weekly-report-module .wr-summary-head .save-footer-btn {
            width: 100%;
        }

        .weekly-report-module .wr-footer-label {
            font-size: 10px;
            line-height: 1.35;
        }

        .weekly-report-module .comm-header-right-btn .btn {
            width: 100%;
        }

        .current-day-report-module.weekly-report-module .card-header .row > [class*="col-"] {
            width: 100%;
        }
    }

    .weekly-report-module .wr-export-print-actions .btn {
        min-height: 38px;
    }

    @media print {
        .weekly-report-module .wr-filter-bar,
        .weekly-report-module .wr-header-toolbar,
        .weekly-report-module .wr-add-panel,
        .weekly-report-module .wr-row-actions,
        .weekly-report-module .wr-scroll-hint,
        .weekly-report-module .save-footer-btn,
        .weekly-report-module .wr-export-print-actions,
        .weekly-report-module .modal,
        .weekly-report-module form[id$="FilterForm"],
        .weekly-report-module .wr-day-hint {
            display: none !important;
        }

        .weekly-report-module .wr-table-wrap {
            overflow: visible !important;
            border: none !important;
        }

        .weekly-report-module .weekly-report-items-table {
            min-width: 100% !important;
        }

        .weekly-report-module .wr-day-section {
            page-break-inside: avoid;
            break-inside: avoid;
        }
    }
</style>
