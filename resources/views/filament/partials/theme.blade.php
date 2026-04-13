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

    .fi-sidebar .fi-sidebar-item-button,
    .fi-sidebar .fi-sidebar-group-button {
        transition:
            background-color 0.18s ease,
            transform 0.2s var(--fil-motion-ease),
            box-shadow 0.2s ease;
    }

    .fi-sidebar .fi-sidebar-item-button:hover,
    .fi-sidebar .fi-sidebar-group-button:hover {
        transform: translateX(-3px);
    }

    [dir='rtl'] .fi-sidebar .fi-sidebar-item-button:hover,
    [dir='rtl'] .fi-sidebar .fi-sidebar-group-button:hover {
        transform: translateX(3px);
    }

    /* صفحة القائمة المفتوحة — متناسقة مع واجباتي (تيل) */
    .fi-main-sidebar .fi-sidebar-item.fi-active > a.fi-sidebar-item-button {
        background-color: color-mix(in srgb, rgb(0 188 212) 22%, transparent) !important;
        box-shadow: inset 3px 0 0 0 rgb(0 139 163);
    }

    .dark .fi-main-sidebar .fi-sidebar-item.fi-active > a.fi-sidebar-item-button {
        background-color: color-mix(in srgb, rgb(0 188 212) 16%, transparent) !important;
        box-shadow: inset 3px 0 0 0 rgb(0 188 212);
    }

    [dir='rtl'] .fi-main-sidebar .fi-sidebar-item.fi-active > a.fi-sidebar-item-button {
        box-shadow: inset -3px 0 0 0 rgb(0 139 163);
    }

    .dark[dir='rtl'] .fi-main-sidebar .fi-sidebar-item.fi-active > a.fi-sidebar-item-button {
        box-shadow: inset -3px 0 0 0 rgb(0 188 212);
    }

    .fi-main-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-label {
        font-weight: 600;
    }

    .fi-topbar {
        animation: fil-fade-in 0.35s ease-out both;
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

        .fi-sidebar .fi-sidebar-item-button,
        .fi-sidebar .fi-sidebar-group-button {
            transition: none;
        }

        .fi-sidebar .fi-sidebar-item-button:hover,
        .fi-sidebar .fi-sidebar-group-button:hover {
            transform: none;
        }
    }
</style>
