@php
    use App\Support\AdminTheme;

    $primary = AdminTheme::primary();
    $secondary = AdminTheme::secondary();
    $primaryLight = AdminTheme::primaryLight();
    $primaryDark = AdminTheme::primaryDark();
    $secondaryLight = AdminTheme::secondaryLight();
    $surfaceBg = AdminTheme::surfaceBackground();
    $surfaceCard = AdminTheme::surfaceCard();
    $surfaceElevated = AdminTheme::surfaceElevated();
    $primaryRgb = AdminTheme::rgbString($primary);
    $secondaryRgb = AdminTheme::rgbString($secondary);
@endphp

<style>
    :root {
        --admin-primary: {{ $primary }};
        --admin-secondary: {{ $secondary }};
        --admin-primary-light: {{ $primaryLight }};
        --admin-primary-dark: {{ $primaryDark }};
        --admin-secondary-light: {{ $secondaryLight }};
        --admin-primary-rgb: {{ $primaryRgb }};
        --admin-secondary-rgb: {{ $secondaryRgb }};

        --ci-bg: {{ $surfaceBg }};
        --ci-card: {{ $surfaceCard }};
        --ci-surface-elevated: {{ $surfaceElevated }};
        --ci-surface-overlay: rgba(var(--admin-primary-rgb), 0.28);
        --ci-surface-overlay-light: rgba(var(--admin-primary-rgb), 0.18);
        --ci-shadow-strong: rgba(var(--admin-primary-rgb), 0.42);
        --ci-on-accent: #ffffff;

        --ci-table-header-bg: rgba(var(--admin-primary-rgb), 0.72);
        --ci-table-toolbar-bg: rgba(var(--admin-primary-rgb), 0.78);
        --ci-table-content-bg: rgba(var(--admin-primary-rgb), 0.4);
        --ci-table-row-bg: rgba(var(--admin-primary-rgb), 0.52);
        --ci-table-row-hover-bg: rgba(var(--admin-primary-rgb), 0.62);
        --ci-table-pagination-bg: rgba(var(--admin-primary-rgb), 0.75);

        --ci-teal: var(--admin-primary);
        --ci-teal-bright: var(--admin-primary-light);
        --ci-orange: var(--admin-secondary);
        --ci-teal-muted-bg: rgba(var(--admin-primary-rgb), 0.2);
        --ci-secondary-muted-bg: rgba(var(--admin-secondary-rgb), 0.18);

        --fil-sidebar-accent: {{ $primaryLight }};
        --fil-sidebar-accent-dim: {{ $primary }};
        --fil-sidebar-active: {{ $secondary }};
        --fil-sidebar-active-light: {{ $secondaryLight }};
        --fil-sidebar-active-rgb: {{ $secondaryRgb }};
        --fil-sidebar-bg-top: {{ $surfaceElevated }};
        --fil-sidebar-bg-bottom: {{ $surfaceBg }};
    }

    html.dark,
    html.dark .fi-body,
    .fi-body,
    .fi-body .fi-main,
    .fi-body .fi-main-ctn,
    .fi-body .fi-page,
    .fi-body .fi-layout {
        background-color: var(--ci-bg) !important;
    }

    .fi-topbar,
    .fi-topbar nav,
    .dark .fi-topbar nav {
        background: linear-gradient(
            90deg,
            var(--ci-surface-elevated) 0%,
            var(--ci-card) 55%,
            var(--ci-bg) 100%
        ) !important;
        background-color: var(--ci-card) !important;
        --tw-ring-color: rgba(var(--admin-primary-rgb), 0.22) !important;
        box-shadow: 0 1px 0 rgba(var(--admin-primary-rgb), 0.28) !important;
    }

    .fi-topbar .fi-input-wrp,
    .fi-topbar input {
        background-color: rgba(var(--admin-primary-rgb), 0.35) !important;
        border-color: rgba(var(--admin-primary-rgb), 0.4) !important;
        color: #fff !important;
    }

    .fi-topbar .fi-icon-btn,
    .fi-topbar .fi-topbar-open-sidebar-btn,
    .fi-topbar .fi-topbar-close-sidebar-btn {
        color: rgba(255, 255, 255, 0.88) !important;
    }

    .fi-logo img {
        width: auto;
        max-width: 11rem;
        object-fit: contain;
    }

    .fi-main-sidebar .fi-sidebar-header,
    .fi-sidebar .fi-sidebar-header {
        height: auto !important;
        min-height: auto;
        padding: 0.0rem 0.0rem 0.0rem !important;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #000000 !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        box-shadow: none !important;
        --tw-ring-color: transparent !important;
    }

    .fi-main-sidebar .fi-sidebar-header > div:first-child,
    .fi-sidebar .fi-sidebar-header > div:first-child {
        width: 100%;
        display: flex;
        justify-content: center;
        line-height: 0;
    }

    .fi-main-sidebar .fi-sidebar-header .fi-logo,
    .fi-sidebar .fi-sidebar-header .fi-logo,
    .fi-main-sidebar .fi-sidebar-header .fi-logo img,
    .fi-sidebar .fi-sidebar-header .fi-logo img {
        display: block;
        height: 260.5rem !important;
        max-height: 06.0rem !important;
        max-width: 100% !important;
        width: 250px !important;
        margin: 0 !important;
        object-fit: contain;
    }

    .fi-simple-header .fi-logo,
    .fi-simple-header .fi-logo img {
        height:250px !important;
        max-height: 105rem !important;
        max-width: 390.7rem !important;
        width: auto !important;
        margin-bottom: 1.85rem;
    }

    .fi-section:not(.fi-section-not-contained),
    .fi-wi-stats-overview-stat,
    .fi-ta-ctn {
        background-color: var(--ci-card) !important;
    }
</style>
