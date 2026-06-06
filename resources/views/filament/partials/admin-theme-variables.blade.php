@php
    use App\Support\AdminTheme;

    $primary = AdminTheme::primary();
    $secondary = AdminTheme::secondary();
    $primaryLight = AdminTheme::primaryLight();
    $primaryDark = AdminTheme::primaryDark();
    $secondaryLight = AdminTheme::secondaryLight();
    $text = AdminTheme::text();
    $textMuted = AdminTheme::textMuted();
    $active = AdminTheme::active();
    $activeLight = AdminTheme::activeLight();
    $background = AdminTheme::background();
    $sidebarBackground = AdminTheme::sidebarBackground();
    $logoHeaderBackground = AdminTheme::logoHeaderBackground();
    $cardBackground = AdminTheme::cardBackground();
    $topbarBackground = AdminTheme::topbarBackground();
    $inputBackground = AdminTheme::inputBackground();
    $inputBorder = AdminTheme::inputBorder();
    $inputText = AdminTheme::inputText();
    $border = AdminTheme::border();
    $tableHeaderBackground = AdminTheme::tableHeaderBackground();
    $tableRowHoverBackground = AdminTheme::tableRowHoverBackground();
    $primaryRgb = AdminTheme::rgbString($primary);
    $secondaryRgb = AdminTheme::rgbString($secondary);
    $activeRgb = AdminTheme::rgbString($active);
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
        --admin-text: {{ $text }};
        --admin-text-muted: {{ $textMuted }};
        --admin-active: {{ $active }};
        --admin-active-light: {{ $activeLight }};
        --admin-active-rgb: {{ $activeRgb }};
        --admin-background: {{ $background }};
        --admin-sidebar-background: {{ $sidebarBackground }};
        --admin-logo-header-background: {{ $logoHeaderBackground }};
        --admin-card-background: {{ $cardBackground }};
        --admin-topbar-background: {{ $topbarBackground }};
        --admin-input-background: {{ $inputBackground }};
        --admin-input-border: {{ $inputBorder }};
        --admin-input-text: {{ $inputText }};
        --admin-border: {{ $border }};

        --ci-bg: var(--admin-background);
        --ci-card: var(--admin-card-background);
        --ci-surface-elevated: var(--admin-card-background);
        --ci-surface-overlay: rgba(0, 0, 0, 0.02);
        --ci-surface-overlay-light: rgba(0, 0, 0, 0.04);
        --ci-shadow-strong: rgba(0, 0, 0, 0.08);
        --ci-on-accent: #ffffff;

        --ci-table-header-bg: {{ $tableHeaderBackground }};
        --ci-table-toolbar-bg: {{ $tableHeaderBackground }};
        --ci-table-content-bg: {{ $cardBackground }};
        --ci-table-row-bg: {{ $cardBackground }};
        --ci-table-row-hover-bg: {{ $tableRowHoverBackground }};
        --ci-table-pagination-bg: {{ $tableHeaderBackground }};

        --ci-teal: var(--admin-primary);
        --ci-teal-bright: var(--admin-primary-light);
        --ci-orange: var(--admin-secondary);
        --ci-teal-muted-bg: rgba(var(--admin-primary-rgb), 0.08);
        --ci-secondary-muted-bg: rgba(var(--admin-secondary-rgb), 0.1);

        --fil-sidebar-accent: {{ $primary }};
        --fil-sidebar-accent-dim: {{ $primaryDark }};
        --fil-sidebar-active: {{ $active }};
        --fil-sidebar-active-light: {{ $activeLight }};
        --fil-sidebar-active-rgb: {{ $activeRgb }};
        --fil-sidebar-bg-top: {{ $sidebarBackground }};
        --fil-sidebar-bg-bottom: {{ $sidebarBackground }};
    }

    html.dark,
    html.dark .fi-body,
    .fi-body,
    .fi-body .fi-main,
    .fi-body .fi-main-ctn,
    .fi-body .fi-page,
    .fi-body .fi-layout {
        background-color: var(--admin-background) !important;
        color: var(--admin-text);
    }

    .fi-topbar,
    .fi-topbar nav,
    .dark .fi-topbar nav {
        background: var(--admin-topbar-background) !important;
        background-color: var(--admin-topbar-background) !important;
        --tw-ring-color: var(--admin-border) !important;
        box-shadow: 0 1px 0 var(--admin-border) !important;
    }

    .fi-topbar .fi-input-wrp,
    .fi-topbar input {
        background-color: var(--admin-input-background) !important;
        border-color: var(--admin-input-border) !important;
        color: var(--admin-input-text) !important;
    }

    .fi-topbar .fi-icon-btn,
    .fi-topbar .fi-topbar-open-sidebar-btn,
    .fi-topbar .fi-topbar-close-sidebar-btn {
        color: var(--admin-text-muted) !important;
    }

    .fi-body .fi-header-heading,
    .fi-body .fi-section-header-heading,
    .fi-body .fi-section-header-description,
    .fi-body .fi-fo-field-wrp-label,
    .fi-body .fi-ta-header-cell-label,
    .fi-body .fi-ta-text,
    .fi-body .fi-ta-text-item,
    .fi-body .fi-wi-stats-overview-stat-label,
    .fi-body .fi-wi-stats-overview-stat-value,
    .fi-body .fi-breadcrumbs-item-label,
    .fi-body .fi-sidebar-item:not(.fi-active) .fi-sidebar-item-label,
    .fi-body .fi-checkbox-label {
        color: var(--admin-text) !important;
    }

    .fi-body .fi-input-wrp {
        background: var(--admin-input-background) !important;
        border: 1px solid var(--admin-input-border) !important;
        box-shadow: none !important;
        --tw-ring-color: var(--admin-input-border) !important;
    }

    .fi-body .fi-input-wrp:focus-within {
        background: color-mix(in srgb, var(--admin-input-background) 85%, var(--admin-secondary) 15%) !important;
        border-color: var(--admin-secondary) !important;
        --tw-ring-color: rgba(var(--admin-secondary-rgb), 0.45) !important;
    }

    .fi-body .fi-input-wrp input,
    .fi-body .fi-input-wrp textarea,
    .fi-body .fi-input-wrp select {
        color: var(--admin-input-text) !important;
        background: transparent !important;
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
        background: var(--admin-logo-header-background) !important;
        border-bottom: 1px solid var(--admin-border) !important;
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
        background-color: var(--admin-card-background) !important;
        border-color: var(--admin-border) !important;
    }

    aside.fi-main-sidebar {
        background: var(--admin-sidebar-background) !important;
        border-inline-end: 1px solid var(--admin-border) !important;
    }

    .fi-body [class*='-page'] h2,
    .fi-body [class*='-page'] .ci-card__head h2 {
        color: var(--admin-text) !important;
    }
</style>
