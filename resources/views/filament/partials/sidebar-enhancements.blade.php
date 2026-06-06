@php
    use App\Support\AdminNavigationGroupLabels;

    // لا نستدعي filament()->getNavigation() هنا — يُولّد روابط بدون tenant ويكسر الصفحة.
    $defaultCollapsedGroups = AdminNavigationGroupLabels::all();
@endphp

<script>
    (() => {
        const versionKey = 'sidebarNavGroupsVersion';
        const storageKey = 'collapsedGroups';
        const version = '2';
        const defaults = @js($defaultCollapsedGroups);

        if (localStorage.getItem(versionKey) !== version) {
            localStorage.setItem(storageKey, JSON.stringify(defaults));
            localStorage.setItem(versionKey, version);
        }
    })();
</script>

<style>
    :root {
        --fil-sidebar-surface: rgba(0, 0, 0, 0.04);
        --fil-sidebar-border: rgba(0, 0, 0, 0.08);
        --fil-sidebar-group-ease: cubic-bezier(0.33, 1, 0.68, 1);
    }

    /* ——— القائمة الجانبية (fi-main-sidebar و fi-sidebar على نفس العنصر) ——— */
    aside.fi-main-sidebar {
        background: var(--admin-sidebar-background) !important;
        border-inline-end: 1px solid var(--admin-border);
    }

    .fi-main-sidebar .fi-sidebar-header {
        background: var(--admin-logo-header-background) !important;
        border-bottom: 1px solid var(--admin-border) !important;
        box-shadow: none !important;
    }

    .fi-main-sidebar .fi-sidebar-nav {
        scrollbar-width: thin;
        scrollbar-color: color-mix(in srgb, var(--fil-sidebar-accent) 40%, transparent) transparent;
    }

    .fi-main-sidebar .fi-sidebar-item:not(.fi-active) .fi-sidebar-item-label {
        color: var(--admin-text-muted) !important;
    }

    .fi-main-sidebar .fi-sidebar-nav::-webkit-scrollbar {
        width: 5px;
    }

    .fi-main-sidebar .fi-sidebar-nav::-webkit-scrollbar-thumb {
        border-radius: 999px;
        background: color-mix(in srgb, var(--fil-sidebar-accent) 35%, transparent);
    }

    .fi-main-sidebar .fi-sidebar-nav-groups {
        gap: 0.35rem !important;
    }

    /* ——— رأس المجموعة (قابل للطي) ——— */
    .fi-main-sidebar .fi-sidebar-group {
        border-radius: 0.75rem;
        padding: 0.15rem;
        transition: background-color 0.25s var(--fil-sidebar-group-ease);
    }

    .fi-main-sidebar .fi-sidebar-group.fi-active {
        background: color-mix(in srgb, var(--fil-sidebar-accent) 6%, transparent);
    }

    .fi-main-sidebar .fi-sidebar-group-button {
        border-radius: 0.625rem !important;
        padding: 0.5rem 0.65rem !important;
        margin: 0 !important;
        border: 1px solid transparent;
        transition:
            background-color 0.22s var(--fil-sidebar-group-ease),
            border-color 0.22s var(--fil-sidebar-group-ease),
            box-shadow 0.22s var(--fil-sidebar-group-ease),
            transform 0.2s var(--fil-sidebar-group-ease) !important;
    }

    .fi-main-sidebar .fi-sidebar-group-button:hover {
        background: var(--fil-sidebar-surface) !important;
        border-color: var(--fil-sidebar-border);
        transform: none !important;
    }

    .fi-main-sidebar .fi-sidebar-group.fi-active > .fi-sidebar-group-button {
        background: color-mix(in srgb, var(--fil-sidebar-accent) 14%, transparent) !important;
        border-color: color-mix(in srgb, var(--fil-sidebar-accent) 28%, transparent);
    }

    .fi-main-sidebar .fi-sidebar-group-label {
        font-size: 0.8125rem !important;
        font-weight: 700 !important;
        letter-spacing: 0.01em;
        color: rgba(0, 0, 0, 0.72) !important;
    }

    .fi-main-sidebar .fi-sidebar-group.fi-active .fi-sidebar-group-label {
        color: var(--fil-sidebar-accent) !important;
    }

    /* سهم صغير للمجموعة — مغلقة: للأسفل | مفتوحة: للأعلى */
    .fi-main-sidebar .fi-sidebar-group-collapse-button {
        width: 1.35rem !important;
        height: 1.35rem !important;
        min-width: 1.35rem !important;
        min-height: 1.35rem !important;
        margin-inline-start: auto;
        border-radius: 0.375rem !important;
        background: rgba(0, 0, 0, 0.06) !important;
        transform: rotate(0deg);
        transition:
            transform 0.32s var(--fil-sidebar-group-ease),
            background-color 0.2s ease !important;
    }

    .fi-main-sidebar .fi-sidebar-group-collapse-button.-rotate-180 {
        transform: rotate(-180deg);
    }

    .fi-main-sidebar .fi-sidebar-group-collapse-button:hover {
        background: color-mix(in srgb, var(--fil-sidebar-accent) 22%, transparent) !important;
    }

    .fi-main-sidebar .fi-sidebar-group-collapse-button svg {
        width: 0.75rem !important;
        height: 0.75rem !important;
        color: var(--fil-sidebar-accent) !important;
    }

    /* ——— عناصر المجموعة المفتوحة ——— */
    .fi-main-sidebar .fi-sidebar-group-items {
        position: relative;
        margin-block: 0.2rem 0.35rem !important;
        margin-inline-start: 0.85rem !important;
        padding-inline-start: 0.65rem !important;
        border-inline-start: 2px solid color-mix(in srgb, var(--fil-sidebar-accent) 22%, transparent);
    }

    @keyframes fil-sidebar-item-reveal {
        from {
            opacity: 0;
            transform: translateX(10px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    [dir='rtl'] .fi-main-sidebar .fi-sidebar-group-items > .fi-sidebar-item {
        animation-name: fil-sidebar-item-reveal-rtl;
    }

    @keyframes fil-sidebar-item-reveal-rtl {
        from {
            opacity: 0;
            transform: translateX(-10px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .fi-main-sidebar .fi-sidebar-group-items > .fi-sidebar-item {
        animation: fil-sidebar-item-reveal 0.38s var(--fil-sidebar-group-ease) both;
    }

    .fi-main-sidebar .fi-sidebar-group-items > .fi-sidebar-item:nth-child(1) {
        animation-delay: 0.04s;
    }

    .fi-main-sidebar .fi-sidebar-group-items > .fi-sidebar-item:nth-child(2) {
        animation-delay: 0.07s;
    }

    .fi-main-sidebar .fi-sidebar-group-items > .fi-sidebar-item:nth-child(3) {
        animation-delay: 0.1s;
    }

    .fi-main-sidebar .fi-sidebar-group-items > .fi-sidebar-item:nth-child(4) {
        animation-delay: 0.13s;
    }

    .fi-main-sidebar .fi-sidebar-group-items > .fi-sidebar-item:nth-child(n + 5) {
        animation-delay: 0.16s;
    }

    /* ——— عنصر القائمة ——— */
    .fi-main-sidebar .fi-sidebar-item-button {
        border-radius: 0.5rem !important;
        padding: 0.45rem 0.55rem !important;
        transition:
            background-color 0.2s var(--fil-sidebar-group-ease),
            padding-inline-start 0.2s var(--fil-sidebar-group-ease),
            box-shadow 0.2s ease !important;
    }

    .fi-main-sidebar .fi-sidebar-item-button:hover {
        background: rgba(0, 0, 0, 0.06) !important;
        transform: none !important;
    }

    .fi-main-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-button {
        background: color-mix(in srgb, var(--fil-sidebar-active) 22%, transparent) !important;
        box-shadow: inset 3px 0 0 0 var(--fil-sidebar-active);
        padding-inline-start: 0.7rem !important;
    }

    [dir='rtl'] .fi-main-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-button {
        box-shadow: inset -3px 0 0 0 var(--fil-sidebar-active);
    }

    /* سهم صغير بجانب الصفحة المفتوحة */
    .fi-main-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-button::after {
        content: '';
        flex-shrink: 0;
        width: 0.45rem;
        height: 0.45rem;
        margin-inline-start: 0.35rem;
        border-inline-end: 2px solid var(--fil-sidebar-active);
        border-block-end: 2px solid var(--fil-sidebar-active);
        transform: rotate(45deg);
        opacity: 0.95;
        animation: fil-sidebar-arrow-pulse 2s ease-in-out infinite;
    }

    [dir='rtl'] .fi-main-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-button::after {
        transform: rotate(-135deg);
    }

    @keyframes fil-sidebar-arrow-pulse {
        0%,
        100% {
            opacity: 0.7;
            transform: rotate(45deg) translate(0, 0);
        }
        50% {
            opacity: 1;
            transform: rotate(45deg) translate(1px, -1px);
        }
    }

    [dir='rtl'] .fi-main-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-button::after {
        animation-name: fil-sidebar-arrow-pulse-rtl;
    }

    @keyframes fil-sidebar-arrow-pulse-rtl {
        0%,
        100% {
            opacity: 0.7;
            transform: rotate(-135deg) translate(0, 0);
        }
        50% {
            opacity: 1;
            transform: rotate(-135deg) translate(-1px, -1px);
        }
    }

    .fi-main-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-label,
    .fi-main-sidebar .fi-sidebar-item.fi-active [class*='text-primary'] {
        font-weight: 700 !important;
        color: var(--fil-sidebar-active-light) !important;
    }

    .fi-main-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-icon {
        color: var(--fil-sidebar-active) !important;
    }

    .fi-main-sidebar .fi-sidebar-item.fi-active [class*='bg-primary'] {
        background-color: var(--fil-sidebar-active) !important;
    }

    /* نقطة العناصر الفرعية → تمييز أوضح عند التفعيل */
    .fi-main-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-grouped-border .rounded-full {
        width: 0.4rem !important;
        height: 0.4rem !important;
        background: var(--fil-sidebar-active) !important;
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--fil-sidebar-active) 30%, transparent);
    }

    @media (prefers-reduced-motion: reduce) {
        .fi-main-sidebar .fi-sidebar-group-items > .fi-sidebar-item,
        .fi-main-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-button::after {
            animation: none !important;
        }

        .fi-main-sidebar .fi-sidebar-group-collapse-button,
        .fi-main-sidebar .fi-sidebar-group-collapse-button svg,
        .fi-main-sidebar .fi-sidebar-group-button,
        .fi-main-sidebar .fi-sidebar-item-button {
            transition: none !important;
        }
    }
</style>
