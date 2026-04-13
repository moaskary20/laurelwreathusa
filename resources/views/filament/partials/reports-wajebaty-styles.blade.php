{{-- تقارير المحاسبة: نفس أسلوب شاشات الرواتب (تعريف الاقتطاعات) — داكن، برتقالي، بطاقات ci-card. يُحمّل بعد wajebaty-theme-styles و payroll-wajebaty-styles. --}}
<style>
    .ci-payroll-wajebaty.ci-reports-page {
        --ci-rep-orange: #ffb300;
        --ci-rep-orange-hover: #ffc107;
        --ci-rep-orange-text: #1a1205;
        --ci-rep-input-bg: rgba(255, 255, 255, 0.09);
        --ci-rep-border: rgba(255, 255, 255, 0.14);
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-title {
        text-align: center;
        font-size: 1.25rem;
        font-weight: 700;
        color: rgba(255, 255, 255, 0.96);
        margin: 0 0 1.25rem;
    }

    /* بطاقات مثل ci-payroll-form-card */
    .ci-payroll-wajebaty.ci-reports-page .ci-reports-card.ci-card.ci-form-inner {
        background: #1a1f26 !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        box-shadow: 0 8px 28px rgba(0, 0, 0, 0.4);
        border-radius: 0.75rem;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1rem;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-filters {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 1rem;
        justify-content: flex-start;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-filters-stack {
        display: flex;
        flex-direction: column;
        gap: 0;
        width: 100%;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-filter-row {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 1rem;
        justify-content: flex-start;
        padding: 0.65rem 0;
        border-bottom: 1px solid var(--ci-line);
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-filter-row:last-of-type {
        border-bottom: none;
        padding-bottom: 0;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-field label {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.92);
        margin-bottom: 0.35rem;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-field input[type='date'],
    .ci-payroll-wajebaty.ci-reports-page .ci-rep-field select {
        border-radius: 0.5rem;
        border: 1px solid var(--ci-rep-border) !important;
        background: var(--ci-rep-input-bg) !important;
        padding: 0.5rem 0.75rem;
        min-width: 11rem;
        color: #fff !important;
        box-shadow: none !important;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-field input[type='date']:focus,
    .ci-payroll-wajebaty.ci-reports-page .ci-rep-field select:focus {
        outline: none;
        border-color: rgba(255, 179, 0, 0.45) !important;
        background: rgba(255, 179, 0, 0.06) !important;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--ci-line);
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-toolbar .fi-btn-color-warning {
        background: var(--ci-rep-orange) !important;
        color: var(--ci-rep-orange-text) !important;
        border: none !important;
        font-weight: 700 !important;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-toolbar .fi-btn-color-warning:hover {
        background: var(--ci-rep-orange-hover) !important;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-toolbar .fi-btn-color-success {
        font-weight: 700 !important;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1.25rem;
        justify-content: flex-end;
        margin-bottom: 0.75rem;
        font-size: 0.9rem;
        color: rgba(255, 255, 255, 0.78);
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-meta span {
        font-weight: 600;
        color: rgba(255, 255, 255, 0.92);
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-report-head {
        text-align: center;
        margin-bottom: 1rem;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-report-head h3 {
        font-size: 1.1rem;
        font-weight: 700;
        color: rgba(255, 255, 255, 0.96);
        margin: 0 0 0.35rem;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-co {
        font-size: 1rem;
        font-weight: 700;
        color: var(--ci-rep-orange) !important;
        margin: 0 0 0.75rem;
        text-align: center;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-report-head .ci-rep-co {
        margin: 0 0 0.35rem;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-period {
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.65);
        margin: 0;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-table-wrap {
        overflow-x: auto;
        border: 1px solid var(--ci-line);
        border-radius: 0.5rem;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-table-wrap table {
        width: 100%;
        border-collapse: collapse;
        min-width: 28rem;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-table-wrap thead th {
        background: rgba(255, 179, 0, 0.12) !important;
        color: rgba(255, 255, 255, 0.95) !important;
        font-weight: 700;
        padding: 0.65rem 0.75rem;
        text-align: center;
        border-bottom: 1px solid var(--ci-line);
        white-space: nowrap;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-table-wrap thead th.ci-rep-col-bal {
        text-align: left;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-table-wrap thead th.ci-rep-num {
        text-align: center;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-table-wrap tbody td {
        padding: 0.55rem 0.75rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        color: rgba(255, 255, 255, 0.88);
        text-align: center;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-table-wrap tbody td.ci-rep-text {
        text-align: right;
        max-width: 16rem;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-table-wrap tbody td.ci-rep-num {
        text-align: left;
        font-variant-numeric: tabular-nums;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-table-wrap tbody tr.ci-rep-section td {
        background: rgba(0, 0, 0, 0.25);
        font-weight: 700;
        color: rgba(255, 255, 255, 0.96);
        padding-top: 0.75rem;
        text-align: right;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-table-wrap tbody tr.ci-rep-subtotal td {
        font-weight: 700;
        background: rgba(255, 255, 255, 0.04);
        color: rgba(255, 255, 255, 0.95);
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-table-wrap tbody tr.ci-rep-net td {
        font-weight: 800;
        background: rgba(255, 179, 0, 0.08);
        color: var(--ci-rep-orange);
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-table-wrap tbody tr.ci-rep-muted td {
        color: rgba(255, 255, 255, 0.55);
        font-style: italic;
        font-size: 0.9rem;
    }

    .ci-payroll-wajebaty.ci-reports-page .ci-rep-empty {
        text-align: center;
        color: rgba(255, 255, 255, 0.55);
        padding: 2rem 1rem;
    }

    .ci-payroll-wajebaty.ci-reports-page tfoot td {
        padding: 0.65rem 0.75rem;
        font-weight: 800;
        background: rgba(0, 0, 0, 0.35);
        border-top: 2px solid var(--ci-line);
        color: rgba(255, 255, 255, 0.96);
    }

    .ci-payroll-wajebaty.ci-reports-page tfoot td.ci-rep-num {
        font-variant-numeric: tabular-nums;
        color: var(--ci-rep-orange);
    }

    @media print {
        .ci-payroll-wajebaty.ci-reports-page .ci-rep-no-print {
            display: none !important;
        }

        .ci-payroll-wajebaty.ci-reports-page {
            background: #fff !important;
        }

        .ci-payroll-wajebaty.ci-reports-page .ci-reports-card.ci-card.ci-form-inner {
            background: #fff !important;
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }

        .ci-payroll-wajebaty.ci-reports-page .ci-rep-title,
        .ci-payroll-wajebaty.ci-reports-page .ci-rep-report-head h3,
        .ci-payroll-wajebaty.ci-reports-page .ci-rep-meta,
        .ci-payroll-wajebaty.ci-reports-page .ci-rep-field label {
            color: #111 !important;
        }

        .ci-payroll-wajebaty.ci-reports-page .ci-rep-co {
            color: #b45309 !important;
        }

        .ci-payroll-wajebaty.ci-reports-page .ci-rep-period {
            color: #444 !important;
        }

        .ci-payroll-wajebaty.ci-reports-page .ci-rep-table-wrap thead th {
            background: #f3f4f6 !important;
            color: #111 !important;
        }

        .ci-payroll-wajebaty.ci-reports-page .ci-rep-table-wrap tbody td {
            color: #111 !important;
            border-color: #eee !important;
        }

        .ci-payroll-wajebaty.ci-reports-page tfoot td {
            background: #f9fafb !important;
            color: #111 !important;
        }
    }
</style>
