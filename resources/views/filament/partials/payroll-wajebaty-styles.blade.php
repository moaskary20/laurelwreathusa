{{-- تصميم موحّد لشاشات الرواتب: خلفية داكنة، أزرار برتقالية، حقول بإطار (مرجع واجباتي). يُحمّل بعد wajebaty-theme-styles. --}}
<style>
    .ci-wajebaty.ci-payroll-wajebaty {
        --ci-payroll-orange: #ffb300;
        --ci-payroll-orange-hover: #ffc107;
        --ci-payroll-orange-text: #1a1205;
        --ci-payroll-input-bg: rgba(255, 255, 255, 0.09);
        --ci-payroll-muted-border: rgba(255, 255, 255, 0.14);
        background: #121212;
        border-radius: 1rem;
    }

    .ci-payroll-wajebaty .ci-payroll-form-card.ci-card {
        background: #1a1f26 !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        box-shadow: 0 8px 28px rgba(0, 0, 0, 0.4);
    }

    /* شريط علوي أهدأ بدل التدرج التركوازي */
    .ci-payroll-wajebaty .ci-banner {
        background: linear-gradient(135deg, #1c222c 0%, #12161c 100%) !important;
        border: 1px solid var(--ci-line);
        color: #fff;
    }

    .ci-payroll-wajebaty .ci-banner__icon {
        background: rgba(255, 179, 0, 0.14) !important;
    }

    .ci-payroll-wajebaty .ci-banner__icon svg {
        color: var(--ci-payroll-orange) !important;
    }

    .ci-payroll-wajebaty .ci-banner__sub {
        color: rgba(255, 255, 255, 0.72) !important;
    }

    /* بطاقة النموذج */
    .ci-payroll-wajebaty .ci-payroll-form-card {
        padding: 1.25rem 1.35rem 1.5rem;
    }

    .ci-payroll-wajebaty .ci-payroll-form-title {
        text-align: right;
        font-size: 1.15rem;
        font-weight: 700;
        color: rgba(255, 255, 255, 0.96);
        margin: 0 0 1.25rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--ci-line);
    }

    /* تسميات الحقول: أبيض مثل المرجع (بدل التركواز) */
    .ci-payroll-wajebaty .ci-form-inner .fi-fo-field-wrp-label,
    .ci-payroll-wajebaty .ci-form-inner .fi-fo-field-wrp-label span {
        color: rgba(255, 255, 255, 0.92) !important;
        font-size: 0.875rem !important;
    }

    .ci-payroll-wajebaty .ci-form-inner .fi-fo-field-wrp-label sup {
        color: #f87171 !important;
    }

    /* حقول بصندوق داكن + زوايا — يتجاوز أسلوب الخط السفلي العام */
    .ci-payroll-wajebaty .ci-form-inner .fi-input-wrp.fi-fo-text-input,
    .ci-payroll-wajebaty .ci-form-inner .fi-input-wrp.fi-fo-select,
    .ci-payroll-wajebaty .ci-form-inner .fi-fo-date-time-picker {
        background: var(--ci-payroll-input-bg) !important;
        border: 1px solid var(--ci-payroll-muted-border) !important;
        border-radius: 0.5rem !important;
        box-shadow: none !important;
        --tw-ring-color: rgba(255, 179, 0, 0.35) !important;
    }

    .ci-payroll-wajebaty .ci-form-inner .fi-input-wrp.fi-fo-text-input:focus-within,
    .ci-payroll-wajebaty .ci-form-inner .fi-input-wrp.fi-fo-select:focus-within,
    .ci-payroll-wajebaty .ci-form-inner .fi-fo-date-time-picker:focus-within {
        background: rgba(255, 179, 0, 0.06) !important;
        border-color: rgba(255, 179, 0, 0.45) !important;
    }

    .ci-payroll-wajebaty .ci-form-inner input[type='text'],
    .ci-payroll-wajebaty .ci-form-inner input[type='number'],
    .ci-payroll-wajebaty .ci-form-inner select,
    .ci-payroll-wajebaty .ci-form-inner textarea {
        color: #fff !important;
    }

    .ci-payroll-wajebaty .ci-form-inner .choices__inner {
        background: transparent !important;
        border: none !important;
        border-radius: 0 !important;
    }

    /* Fieldset / أقسام */
    .ci-payroll-wajebaty .ci-form-inner fieldset.fi-fieldset {
        border-color: var(--ci-line) !important;
        border-radius: 0.65rem !important;
        padding: 0.85rem 1rem !important;
        background: rgba(0, 0, 0, 0.2) !important;
    }

    .ci-payroll-wajebaty .ci-form-inner fieldset.fi-fieldset > legend {
        color: rgba(255, 255, 255, 0.9) !important;
        font-weight: 600 !important;
    }

    /* أزرار النموذج: برتقالي = حفظ، رمادي داكن = إلغاء */
    .ci-payroll-wajebaty .fi-form-actions {
        margin-top: 1.5rem;
        padding-top: 1.15rem;
        border-top: 1px solid var(--ci-line);
    }

    .ci-payroll-wajebaty .fi-form-actions.fi-sticky {
        background: var(--ci-card) !important;
        --tw-ring-color: var(--ci-line) !important;
    }

    .ci-payroll-wajebaty .fi-form-actions .fi-btn-color-primary {
        background: var(--ci-payroll-orange) !important;
        color: var(--ci-payroll-orange-text) !important;
        border: none !important;
        font-weight: 700 !important;
    }

    .ci-payroll-wajebaty .fi-form-actions .fi-btn-color-primary:hover {
        background: var(--ci-payroll-orange-hover) !important;
    }

    .ci-payroll-wajebaty .fi-form-actions .fi-btn-color-gray {
        background: rgba(35, 40, 48, 0.95) !important;
        color: rgba(255, 255, 255, 0.92) !important;
        border: 1px solid var(--ci-payroll-muted-border) !important;
    }

    .ci-payroll-wajebaty .fi-form-actions .fi-btn-color-gray:hover {
        background: rgba(48, 54, 64, 0.98) !important;
    }

    /* زر العودة برتقالي مثل المرجع (رابط أو primary) */
    .ci-payroll-wajebaty .fi-form-actions a.fi-btn-color-primary,
    .ci-payroll-wajebaty .fi-form-actions .fi-btn-color-primary.fi-ac-action {
        background: var(--ci-payroll-orange) !important;
        color: var(--ci-payroll-orange-text) !important;
    }

    @media print {
        .fi-sidebar,
        .fi-sidebar-close-overlay,
        .fi-topbar,
        .fi-header {
            display: none !important;
        }

        .ci-wajebaty.ci-payroll-wajebaty {
            background: #fff !important;
            color: #111 !important;
        }

        .ci-payroll-wajebaty .ci-payroll-form-card.ci-card,
        .ci-payroll-wajebaty .ci-banner,
        .ci-payroll-wajebaty .ci-table-shell .fi-ta {
            background: #fff !important;
            border-color: #ddd !important;
            box-shadow: none !important;
        }

        .ci-payroll-wajebaty .ci-table-shell [class*='fi-table-header-cell'] {
            background: #f3f4f6 !important;
            color: #111 !important;
        }

        .ci-payroll-wajebaty .ci-table-shell .fi-ta-text,
        .ci-payroll-wajebaty .ci-table-shell .fi-ta-text-item {
            color: #111 !important;
        }

        .ci-payroll-wajebaty .fi-form-actions {
            display: none !important;
        }
    }

    /* جداول القوائم: ترويسة بلمسة برتقالية خفيفة، زر إضافة برتقالي */
    .ci-payroll-wajebaty .ci-table-shell .fi-ta-header-toolbar .fi-btn-color-primary,
    .ci-payroll-wajebaty .ci-table-shell .fi-ta-header-toolbar .fi-color-primary {
        background: var(--ci-payroll-orange) !important;
        color: var(--ci-payroll-orange-text) !important;
        border: none !important;
        font-weight: 700 !important;
    }

    .ci-payroll-wajebaty .ci-table-shell .fi-ta-header-toolbar .fi-btn-color-primary:hover {
        background: var(--ci-payroll-orange-hover) !important;
    }

    .ci-payroll-wajebaty .ci-table-shell .fi-ta-header-toolbar .fi-btn-color-warning {
        background: rgba(255, 152, 0, 0.92) !important;
        color: #1a1006 !important;
    }

    .ci-payroll-wajebaty .ci-table-shell [class*='fi-table-header-cell'] {
        background: rgba(255, 179, 0, 0.1) !important;
        color: rgba(255, 255, 255, 0.95) !important;
        text-align: center !important;
        white-space: nowrap;
    }

    .ci-payroll-wajebaty .ci-table-shell .fi-ta-text,
    .ci-payroll-wajebaty .ci-table-shell .fi-ta-text-item {
        text-align: center;
    }

    .ci-payroll-wajebaty .ci-table-shell .fi-ac-btn-color-primary {
        background: var(--ci-payroll-orange) !important;
        color: var(--ci-payroll-orange-text) !important;
    }

    .ci-payroll-wajebaty .ci-table-shell .fi-ac-btn {
        border-radius: 9999px !important;
        min-width: 2.25rem;
        min-height: 2.25rem;
    }

    .ci-payroll-wajebaty .ci-table-shell .fi-ac-btn-color-danger {
        background: rgba(248, 113, 113, 0.12) !important;
        color: #fecaca !important;
        border: 1px solid rgba(248, 113, 113, 0.35) !important;
    }

    .ci-payroll-wajebaty .ci-card__head svg {
        color: var(--ci-payroll-orange) !important;
    }

    .ci-payroll-wajebaty .ci-table-shell .fi-ta-empty-state-icon {
        color: rgba(255, 179, 0, 0.45) !important;
    }

    /* كشف الرواتب: أعمدة كثيرة */
    .ci-payroll-wajebaty .ci-table-shell--scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .ci-payroll-wajebaty .ci-table-shell--scroll .fi-ta {
        min-width: 72rem;
    }
</style>
