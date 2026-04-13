<style>
    .ci-wajebaty {
        --ci-bg: #0b0e11;
        --ci-card: #1e2530;
        --ci-teal: #008ba3;
        --ci-teal-bright: #00bcd4;
        --ci-orange: #ffb300;
        --ci-muted: rgba(255, 255, 255, 0.62);
        --ci-line: rgba(255, 255, 255, 0.12);
        font-family: 'Tajawal', ui-sans-serif, system-ui, sans-serif;
        background: var(--ci-bg);
        border-radius: 1rem;
        animation: ci-fade-up 0.55s cubic-bezier(0.33, 1, 0.68, 1) both;
    }

    @keyframes ci-fade-up {
        from {
            opacity: 0;
            transform: translateY(16px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes ci-fade-in {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    .ci-wajebaty .ci-banner {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1.25rem;
        padding: 1.25rem 1.5rem;
        border-radius: 1.25rem;
        background: linear-gradient(135deg, #008ba3 0%, #006978 100%);
        color: #fff;
        margin-bottom: 1.5rem;
        animation: ci-fade-in 0.45s ease-out 0.08s both;
    }

    .ci-wajebaty .ci-banner__main {
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 0;
        flex: 1 1 12rem;
    }

    .ci-wajebaty .ci-banner__actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        justify-content: flex-end;
    }

    .ci-wajebaty .ci-banner__actions .fi-btn {
        border-radius: 0.55rem !important;
        font-weight: 700 !important;
    }

    .ci-wajebaty .ci-banner__actions .fi-btn-color-primary,
    .ci-wajebaty .ci-banner__actions .fi-color-primary {
        background: #fff !important;
        color: var(--ci-teal) !important;
        border: none !important;
    }

    .ci-wajebaty .ci-banner__icon {
        flex-shrink: 0;
        width: 3.25rem;
        height: 3.25rem;
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.15);
        display: grid;
        place-items: center;
    }

    .ci-wajebaty .ci-banner__icon svg {
        width: 1.85rem;
        height: 1.85rem;
        color: #fff;
    }

    .ci-wajebaty .ci-banner__title {
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1.3;
    }

    .ci-wajebaty .ci-banner__sub {
        font-size: 0.9rem;
        color: rgba(255, 255, 255, 0.88);
        margin-top: 0.2rem;
    }

    .ci-wajebaty .ci-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: flex-end;
    }

    .ci-wajebaty .ci-stat {
        min-width: 5.5rem;
        padding: 0.45rem 0.75rem;
        border-radius: 0.65rem;
        text-align: center;
        font-size: 0.78rem;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
    }

    .ci-wajebaty .ci-stat--accent {
        background: #fff;
        color: var(--ci-teal);
        border-color: transparent;
        font-weight: 700;
    }

    .ci-wajebaty .ci-stat__val {
        font-size: 1.1rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .ci-wajebaty .ci-stat__lbl {
        opacity: 0.9;
        margin-top: 0.1rem;
    }

    .ci-wajebaty .ci-grid {
        display: grid;
        gap: 1.25rem;
        grid-template-columns: 1fr;
    }

    @media (min-width: 1024px) {
        .ci-wajebaty .ci-grid {
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.35fr);
        }
    }

    .ci-wajebaty .ci-card {
        background: var(--ci-card);
        border-radius: 1rem;
        border: 1px solid var(--ci-line);
        padding: 1.15rem 1.25rem;
        animation: ci-fade-up 0.5s cubic-bezier(0.33, 1, 0.68, 1) both;
    }

    .ci-wajebaty .ci-card:nth-child(1) {
        animation-delay: 0.1s;
    }

    .ci-wajebaty .ci-card:nth-child(2) {
        animation-delay: 0.18s;
    }

    .ci-wajebaty .ci-card__head {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
        padding-bottom: 0.65rem;
        border-bottom: 1px solid var(--ci-line);
    }

    .ci-wajebaty .ci-card__head h2 {
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
    }

    .ci-wajebaty .ci-card__head svg {
        width: 1.25rem;
        height: 1.25rem;
        color: var(--ci-teal-bright);
    }

    .ci-wajebaty .ci-table-wrap {
        overflow: auto;
        border-radius: 0.65rem;
        border: 1px solid var(--ci-line);
    }

    .ci-wajebaty table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .ci-wajebaty thead th {
        background: rgba(0, 139, 163, 0.2);
        color: #e8f7fa;
        font-weight: 700;
        padding: 0.55rem 0.65rem;
        text-align: center;
        border-bottom: 1px solid var(--ci-line);
    }

    .ci-wajebaty tbody td {
        padding: 0.55rem 0.65rem;
        text-align: center;
        color: var(--ci-muted);
        border-bottom: 1px solid var(--ci-line);
    }

    .ci-wajebaty tbody tr:last-child td {
        border-bottom: none;
    }

    .ci-wajebaty .ci-empty {
        text-align: center;
        color: var(--ci-muted);
        padding: 2rem 0.5rem;
    }

    .ci-wajebaty .ci-form-inner .fi-fo-field-wrp-label {
        color: var(--ci-teal-bright) !important;
        font-size: 0.8rem !important;
    }

    .ci-wajebaty .ci-form-inner .fi-fo-text-input,
    .ci-wajebaty .ci-form-inner .fi-fo-textarea,
    .ci-wajebaty .ci-form-inner .fi-fo-select {
        border: none !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        background: transparent !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.22) !important;
    }

    .ci-wajebaty .ci-form-inner input,
    .ci-wajebaty .ci-form-inner textarea,
    .ci-wajebaty .ci-form-inner select {
        color: #fff !important;
        background: transparent !important;
    }

    .ci-wajebaty .ci-form-inner .choices__inner {
        background: transparent !important;
        border: none !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.22) !important;
        border-radius: 0 !important;
    }

    .ci-wajebaty .ci-form-inner .fi-section-header {
        color: #fff !important;
    }

    .ci-wajebaty .ci-form-inner .fi-section-content {
        border-color: var(--ci-line) !important;
    }

    .ci-wajebaty .ci-form-inner .fi-btn {
        border-radius: 0.5rem;
    }

    .ci-wajebaty .ci-form-inner .fi-btn-color-primary {
        background: var(--ci-teal-bright) !important;
        color: #0b0e11 !important;
    }

    .ci-wajebaty .ci-form-inner .ci-logo-field {
        padding: 0.85rem 1rem;
        border-radius: 0.65rem;
        background: linear-gradient(90deg, #008ba3, #00a3bf);
        margin-top: 0.75rem;
    }

    .ci-wajebaty .ci-form-inner .ci-logo-field label {
        color: #fff !important;
    }

    .ci-wajebaty .ci-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0.65rem;
        margin-top: 1.25rem;
        padding-top: 1rem;
        border-top: 1px solid var(--ci-line);
    }

    .ci-wajebaty .ci-btn-save {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.55rem 1.15rem;
        border-radius: 0.55rem;
        font-weight: 700;
        font-size: 0.9rem;
        background: var(--ci-teal-bright);
        color: #0b0e11;
        border: none;
        cursor: pointer;
        transition:
            transform 0.15s ease,
            box-shadow 0.15s ease;
    }

    .ci-wajebaty .ci-btn-save:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(0, 188, 212, 0.35);
    }

    .ci-wajebaty .ci-btn-cancel {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.55rem 1.15rem;
        border-radius: 0.55rem;
        font-weight: 700;
        font-size: 0.9rem;
        background: rgba(0, 188, 212, 0.18);
        color: #e0f7fa;
        border: 1px solid rgba(0, 188, 212, 0.45);
        cursor: pointer;
        transition: background 0.15s ease;
    }

    .ci-wajebaty .ci-btn-cancel:hover {
        background: rgba(0, 188, 212, 0.28);
    }

    /* Filament data table inside resource shell */
    .ci-wajebaty .ci-table-shell .fi-ta {
        border-radius: 0.75rem;
        border: 1px solid var(--ci-line);
        overflow: hidden;
        background: rgba(0, 0, 0, 0.28);
    }

    .ci-wajebaty .ci-table-shell .fi-ta-header-ctn {
        background: rgba(26, 32, 41, 0.98) !important;
    }

    .ci-wajebaty .ci-table-shell .fi-ta-header-toolbar {
        background: rgba(26, 32, 41, 0.98) !important;
        border-color: var(--ci-line) !important;
    }

    .ci-wajebaty .ci-table-shell .fi-ta-content {
        background: rgba(15, 18, 24, 0.65) !important;
        border-color: var(--ci-line) !important;
    }

    .ci-wajebaty .ci-table-shell .fi-ta-record {
        background: rgba(30, 37, 48, 0.92) !important;
    }

    .ci-wajebaty .ci-table-shell .fi-ta-record:hover {
        background: rgba(40, 50, 64, 0.96) !important;
    }

    .ci-wajebaty .ci-table-shell .fi-ta-pagination {
        background: rgba(26, 32, 41, 0.95) !important;
        border-color: var(--ci-line) !important;
    }

    .ci-wajebaty .ci-table-shell [class*='fi-table-header-cell'] {
        color: #e8f7fa !important;
        background: rgba(0, 139, 163, 0.2) !important;
    }

    .ci-wajebaty .ci-table-shell .fi-ta-text,
    .ci-wajebaty .ci-table-shell .fi-ta-text-item {
        color: rgba(255, 255, 255, 0.88) !important;
    }

    .ci-wajebaty .ci-table-shell input.fi-input-wrp,
    .ci-wajebaty .ci-table-shell .fi-input-wrp {
        background: rgba(255, 255, 255, 0.06) !important;
        border-color: var(--ci-line) !important;
        color: #fff !important;
    }

    .ci-wajebaty .ci-table-shell .fi-ac-btn {
        border-radius: 0.45rem !important;
    }

    .ci-wajebaty .ci-form-inner .fi-fo-field-wrp:focus-within .fi-fo-field-wrp-label {
        color: var(--ci-teal-bright) !important;
    }

    .ci-wajebaty .ci-form-inner .fi-fo-field-wrp:focus-within .fi-fo-text-input,
    .ci-wajebaty .ci-form-inner .fi-fo-field-wrp:focus-within .fi-fo-textarea,
    .ci-wajebaty .ci-form-inner .fi-fo-field-wrp:focus-within .fi-fo-select {
        background: rgba(0, 188, 212, 0.1) !important;
    }

    .ci-wajebaty .ci-form-inner .fi-fo-checkbox-list-option-label {
        color: rgba(255, 255, 255, 0.88) !important;
    }

    .ci-wajebaty .ci-form-inner .fi-section-header-heading {
        color: #fff !important;
    }

    .ci-wajebaty .ci-form-inner .fi-fo-placeholder {
        color: rgba(255, 255, 255, 0.95) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.22);
        padding-bottom: 0.4rem;
        min-height: 2.25rem;
    }

    @media (prefers-reduced-motion: reduce) {
        .ci-wajebaty,
        .ci-wajebaty .ci-banner,
        .ci-wajebaty .ci-card {
            animation: none !important;
        }

        .ci-wajebaty .ci-btn-save:hover {
            transform: none;
        }
    }
</style>
