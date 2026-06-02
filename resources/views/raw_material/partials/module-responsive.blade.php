<style>
    /* Raw Material module — responsive layout */
    .raw-material-module .comm-header-right-btn {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
        justify-content: flex-end;
    }

    .raw-material-module .order-product-table {
        min-width: 680px;
    }

    .raw-material-module .totals-box {
        max-width: 100%;
    }

    .raw-material-module .rm-form-actions {
        flex-wrap: wrap;
    }

    .raw-material-module .icon-form .form-control.is-invalid,
    .raw-material-module .select2-container--default .select2-selection.is-invalid {
        border-color: var(--bs-danger) !important;
    }

    .raw-material-module .select2-container .select2-selection.is-invalid {
        border-color: var(--bs-danger) !important;
    }

    @media (max-width: 991.98px) {
        .raw-material-module .cls-form-right {
            width: 100%;
        }

        .raw-material-module .comm-header-right-btn {
            justify-content: flex-start;
            width: 100%;
        }
    }

    @media (max-width: 575.98px) {
        .raw-material-module .comm-header-right-btn .btn,
        .raw-material-module .comm-header-right-btn .btn-group {
            width: 100%;
        }

        .raw-material-module .comm-header-right-btn .btn-group > .btn {
            width: 100%;
        }

        .raw-material-module .comm-header-right-btn .dropdown-menu {
            width: 100%;
        }

        .raw-material-module .rm-form-actions {
            flex-direction: column;
            align-items: stretch !important;
        }

        .raw-material-module .rm-form-actions .btn {
            width: 100%;
        }

        .raw-material-module .order-product-table .row-actions {
            white-space: nowrap;
        }
    }
</style>
