<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-warehouse-definition-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        wire:key="warehouse-definition-{{ \Filament\Facades\Filament::getTenant()?->getKey() }}"
        dir="rtl"
    >
        @include('filament.partials.wajebaty-theme-styles')

        <style>
            .ci-warehouse-definition-page .ci-card__head-wh h2 {
                font-size: 1.15rem;
                font-weight: 700;
                color: #fff;
            }

            .ci-warehouse-definition-page .ci-table-shell .fi-ta {
                border-radius: 0.75rem;
                border: 1px solid var(--ci-line);
                overflow: hidden;
                background: var(--ci-surface-overlay);
            }

            .ci-warehouse-definition-page .ci-table-shell .fi-ta-header-ctn {
                background: var(--ci-table-toolbar-bg) !important;
            }

            .ci-warehouse-definition-page .ci-table-shell .fi-ta-header-toolbar {
                background: var(--ci-table-toolbar-bg) !important;
                border-color: var(--ci-line) !important;
            }

            .ci-warehouse-definition-page .ci-table-shell .fi-ta-content {
                background: var(--ci-table-content-bg) !important;
                border-color: var(--ci-line) !important;
            }

            .ci-warehouse-definition-page .ci-table-shell .fi-ta-record {
                background: var(--ci-table-row-bg) !important;
            }

            .ci-warehouse-definition-page .ci-table-shell .fi-ta-record:hover {
                background: var(--ci-table-row-hover-bg) !important;
            }

            .ci-warehouse-definition-page .ci-table-shell [class*='fi-table-header-cell'] {
                color: #e8f7fa !important;
                background: var(--ci-teal-muted-bg) !important;
                text-align: center !important;
            }

            .ci-warehouse-definition-page .ci-table-shell .fi-ta-text,
            .ci-warehouse-definition-page .ci-table-shell .fi-ta-text-item {
                color: rgba(255, 255, 255, 0.88) !important;
            }

            .ci-warehouse-definition-page .ci-table-shell .fi-ac-btn {
                border-radius: 9999px !important;
                min-width: 2.25rem;
                min-height: 2.25rem;
            }

            .ci-warehouse-definition-page .ci-table-shell .fi-ac-btn-color-primary {
                background: var(--ci-teal-bright) !important;
                color: var(--ci-on-accent) !important;
            }

            .ci-warehouse-definition-page .ci-table-shell .fi-ac-btn-color-danger {
                background: rgba(0, 188, 212, 0.22) !important;
                color: #e0f7fa !important;
                border: 1px solid rgba(0, 188, 212, 0.45) !important;
            }
        </style>

        <div class="ci-banner">
            <div class="ci-banner__main">
                <div class="ci-banner__icon" aria-hidden="true">
                    <x-filament::icon icon="heroicon-o-building-storefront" class="h-8 w-8" />
                </div>
                <div class="min-w-0">
                    <div class="ci-banner__title">تعريف المستودعات</div>
                    <div class="ci-banner__sub">إدارة أسماء المستودعات بالعربي والإنجليزي</div>
                </div>
            </div>
        </div>

        <div class="ci-card ci-form-inner ci-warehouse-definition-page">
            <div class="ci-card__head ci-card__head-wh">
                <x-filament::icon icon="heroicon-o-table-cells" class="h-5 w-5" />
                <h2>المستودعات</h2>
            </div>

            <div class="ci-table-shell">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
