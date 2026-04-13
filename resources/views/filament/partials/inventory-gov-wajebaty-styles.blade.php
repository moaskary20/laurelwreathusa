{{-- تصميم موحّد لسندات/طلبات المستودع (نمط واجباتي: داكن، برتقالي، حقول بإطار). يُحمّل بعد wajebaty-theme-styles. --}}
<style>
    .ci-wajebaty.ci-inventory-gov-wajebaty {
        --ci-gov-orange: #ffb300;
        --ci-gov-orange-hover: #ffc107;
        --ci-gov-orange-text: #1a1205;
        --ci-gov-input-bg: rgba(255, 255, 255, 0.09);
        --ci-gov-muted-border: rgba(255, 255, 255, 0.14);
        background: #121212;
        border-radius: 1rem;
    }

    .ci-inventory-gov-wajebaty .ci-gov-title {
        text-align: center;
        font-size: 1.2rem;
        font-weight: 700;
        color: rgba(255, 255, 255, 0.96);
        margin: 0 0 1.25rem;
    }

    .ci-inventory-gov-wajebaty .ci-gov-box.ci-form-inner {
        background: #1a1f26 !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.75rem;
        box-shadow: 0 8px 28px rgba(0, 0, 0, 0.4);
        padding: 1.25rem 1.5rem;
        margin-bottom: 1rem;
    }

    .ci-inventory-gov-wajebaty .ci-banner {
        background: linear-gradient(135deg, #1c222c 0%, #12161c 100%) !important;
        border: 1px solid var(--ci-line);
        color: #fff;
    }

    .ci-inventory-gov-wajebaty .ci-banner__icon {
        background: rgba(255, 179, 0, 0.14) !important;
    }

    .ci-inventory-gov-wajebaty .ci-banner__icon svg {
        color: var(--ci-gov-orange) !important;
    }

    .ci-inventory-gov-wajebaty .ci-banner__sub {
        color: rgba(255, 255, 255, 0.72) !important;
    }

    .ci-inventory-gov-wajebaty .ci-form-inner .fi-fo-field-wrp-label,
    .ci-inventory-gov-wajebaty .ci-form-inner .fi-fo-field-wrp-label span {
        color: rgba(255, 255, 255, 0.92) !important;
        font-size: 0.875rem !important;
    }

    .ci-inventory-gov-wajebaty .ci-form-inner .fi-fo-field-wrp-label sup {
        color: #f87171 !important;
    }

    .ci-inventory-gov-wajebaty .ci-form-inner .fi-input-wrp.fi-fo-text-input,
    .ci-inventory-gov-wajebaty .ci-form-inner .fi-input-wrp.fi-fo-select,
    .ci-inventory-gov-wajebaty .ci-form-inner .fi-fo-date-time-picker {
        background: var(--ci-gov-input-bg) !important;
        border: 1px solid var(--ci-gov-muted-border) !important;
        border-radius: 0.5rem !important;
        box-shadow: none !important;
        --tw-ring-color: rgba(255, 179, 0, 0.35) !important;
    }

    .ci-inventory-gov-wajebaty .ci-form-inner .fi-input-wrp.fi-fo-text-input:focus-within,
    .ci-inventory-gov-wajebaty .ci-form-inner .fi-input-wrp.fi-fo-select:focus-within,
    .ci-inventory-gov-wajebaty .ci-form-inner .fi-fo-date-time-picker:focus-within {
        background: rgba(255, 179, 0, 0.06) !important;
        border-color: rgba(255, 179, 0, 0.45) !important;
    }

    .ci-inventory-gov-wajebaty .ci-form-inner input[type='text'],
    .ci-inventory-gov-wajebaty .ci-form-inner input[type='number'],
    .ci-inventory-gov-wajebaty .ci-form-inner select,
    .ci-inventory-gov-wajebaty .ci-form-inner textarea {
        color: #fff !important;
    }

    .ci-inventory-gov-wajebaty .ci-form-inner .choices__inner {
        background: transparent !important;
        border: none !important;
        border-radius: 0 !important;
    }

    .ci-inventory-gov-wajebaty .ci-form-inner fieldset.fi-fieldset {
        border-color: var(--ci-line) !important;
        border-radius: 0.65rem !important;
        padding: 0.85rem 1rem !important;
        background: rgba(0, 0, 0, 0.2) !important;
    }

    .ci-inventory-gov-wajebaty .ci-form-inner fieldset.fi-fieldset > legend {
        color: rgba(255, 255, 255, 0.9) !important;
        font-weight: 600 !important;
    }

    .ci-inventory-gov-wajebaty .fi-form-actions {
        margin-top: 1.5rem;
        padding-top: 1.15rem;
        border-top: 1px solid var(--ci-line);
    }

    /* إخفاء شريط أزرار مكرّر إن وُجد (نسخة احتياطية مع إصلاح PHP) */
    .ci-inventory-gov-wajebaty .fi-form-actions ~ .fi-form-actions {
        display: none !important;
    }

    .ci-inventory-gov-wajebaty .fi-form-actions.fi-sticky {
        background: var(--ci-card) !important;
        --tw-ring-color: var(--ci-line) !important;
    }

    .ci-inventory-gov-wajebaty .fi-form-actions .fi-btn-color-primary {
        background: var(--ci-gov-orange) !important;
        color: var(--ci-gov-orange-text) !important;
        border: none !important;
        font-weight: 700 !important;
    }

    .ci-inventory-gov-wajebaty .fi-form-actions .fi-btn-color-primary:hover {
        background: var(--ci-gov-orange-hover) !important;
    }

    .ci-inventory-gov-wajebaty .fi-form-actions .fi-btn-color-gray {
        background: rgba(35, 40, 48, 0.95) !important;
        color: rgba(255, 255, 255, 0.92) !important;
        border: 1px solid var(--ci-gov-muted-border) !important;
    }

    .ci-inventory-gov-wajebaty .fi-form-actions .fi-btn-color-gray:hover {
        background: rgba(48, 54, 64, 0.98) !important;
    }

    .ci-inventory-gov-wajebaty .fi-form-actions a.fi-btn-color-primary {
        background: var(--ci-gov-orange) !important;
        color: var(--ci-gov-orange-text) !important;
    }

    .ci-inventory-gov-wajebaty .ci-table-shell .fi-ta-header-toolbar .fi-btn-color-primary,
    .ci-inventory-gov-wajebaty .ci-table-shell .fi-ta-header-toolbar .fi-color-primary {
        background: var(--ci-gov-orange) !important;
        color: var(--ci-gov-orange-text) !important;
        border: none !important;
        font-weight: 700 !important;
    }

    .ci-inventory-gov-wajebaty .ci-table-shell .fi-ta-header-toolbar .fi-btn-color-primary:hover {
        background: var(--ci-gov-orange-hover) !important;
    }

    .ci-inventory-gov-wajebaty .ci-table-shell .fi-ta-header-toolbar .fi-btn-color-warning {
        background: rgba(255, 152, 0, 0.92) !important;
        color: #1a1006 !important;
    }

    .ci-inventory-gov-wajebaty .ci-table-shell [class*='fi-table-header-cell'] {
        background: rgba(255, 179, 0, 0.1) !important;
        color: rgba(255, 255, 255, 0.95) !important;
        text-align: center !important;
        white-space: nowrap;
    }

    .ci-inventory-gov-wajebaty .ci-table-shell .fi-ta-text,
    .ci-inventory-gov-wajebaty .ci-table-shell .fi-ta-text-item {
        text-align: center;
    }

    .ci-inventory-gov-wajebaty .ci-table-shell .fi-ac-btn-color-primary {
        background: var(--ci-gov-orange) !important;
        color: var(--ci-gov-orange-text) !important;
    }

    .ci-inventory-gov-wajebaty .ci-table-shell .fi-ac-btn {
        border-radius: 9999px !important;
        min-width: 2.25rem;
        min-height: 2.25rem;
    }

    .ci-inventory-gov-wajebaty .ci-table-shell .fi-ac-btn-color-danger {
        background: rgba(248, 113, 113, 0.12) !important;
        color: #fecaca !important;
        border: 1px solid rgba(248, 113, 113, 0.35) !important;
    }

    .ci-inventory-gov-wajebaty .ci-card__head svg {
        color: var(--ci-gov-orange) !important;
    }

    .ci-inventory-gov-wajebaty .ci-table-shell .fi-ta-empty-state-icon {
        color: rgba(255, 179, 0, 0.45) !important;
    }

    @media print {
        .fi-sidebar,
        .fi-sidebar-close-overlay,
        .fi-topbar,
        .fi-header {
            display: none !important;
        }

        .ci-wajebaty.ci-inventory-gov-wajebaty {
            background: #fff !important;
            color: #111 !important;
        }

        .ci-inventory-gov-wajebaty .ci-gov-box.ci-form-inner,
        .ci-inventory-gov-wajebaty .ci-banner,
        .ci-inventory-gov-wajebaty .ci-card,
        .ci-inventory-gov-wajebaty .ci-table-shell .fi-ta {
            background: #fff !important;
            border-color: #ddd !important;
            box-shadow: none !important;
        }

        .ci-inventory-gov-wajebaty .ci-table-shell [class*='fi-table-header-cell'] {
            background: #f3f4f6 !important;
            color: #111 !important;
        }

        .ci-inventory-gov-wajebaty .ci-table-shell .fi-ta-text,
        .ci-inventory-gov-wajebaty .ci-table-shell .fi-ta-text-item {
            color: #111 !important;
        }

        .ci-inventory-gov-wajebaty .fi-form-actions {
            display: none !important;
        }
    }
</style>
