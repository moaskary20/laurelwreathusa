@include('filament.partials.admin-theme-variables')

<style>
    :root {
        --fil-motion-ease: cubic-bezier(0.33, 1, 0.68, 1);
        --fil-motion-duration: 0.45s;
    }

    .fi-body {
        font-family: 'Tajawal', ui-sans-serif, system-ui, sans-serif !important;
    }

    .fi-body :where(input, textarea, select, button, .fi-wi-stats-overview-stat-value) {
        font-family: inherit !important;
    }

    @keyframes fil-fade-up {
        from {
            opacity: 0;
            transform: translateY(14px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fil-fade-in {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    .fi-page {
        animation: fil-fade-up var(--fil-motion-duration) var(--fil-motion-ease) both;
    }

    .fi-ta-ctn,
    .fi-fo-form,
    .fi-wi-stats-overview-stat,
    .fi-section:not(.fi-section-not-contained) {
        animation: fil-fade-up calc(var(--fil-motion-duration) + 0.05s) var(--fil-motion-ease) both;
        animation-delay: 0.06s;
    }

    .fi-topbar {
        animation: fil-fade-in 0.35s ease-out both;
    }

    .fi-topbar nav {
        background: var(--admin-topbar-background) !important;
        background-color: var(--admin-topbar-background) !important;
    }

    .fi-main-sidebar {
        animation: fil-fade-in 0.5s ease-out both;
    }

    @media (prefers-reduced-motion: reduce) {
        .fi-page,
        .fi-ta-ctn,
        .fi-fo-form,
        .fi-wi-stats-overview-stat,
        .fi-section:not(.fi-section-not-contained),
        .fi-topbar,
        .fi-main-sidebar {
            animation: none !important;
        }

    }
</style>

@include('filament.partials.sidebar-enhancements')
