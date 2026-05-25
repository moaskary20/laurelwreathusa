<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-trade-discount-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        wire:key="trade-discount-{{ \Filament\Facades\Filament::getTenant()?->getKey() }}"
    >
        @include('filament.partials.wajebaty-theme-styles')

        <style>
            .ci-trade-discount-page .ci-card__head-trade h2 {
                font-size: 1.15rem;
                font-weight: 700;
                color: #fff;
            }

            .ci-trade-discount-page .ci-trade-discount-rate-field .fi-fo-field-wrp-label {
                color: var(--ci-teal-bright) !important;
            }

            .ci-trade-discount-page .ci-table-shell .fi-ta {
                border-radius: 0.75rem;
                border: 1px solid var(--ci-line);
                overflow: hidden;
                background: var(--ci-surface-overlay);
            }

            .ci-trade-discount-page .ci-table-shell .fi-ta-header-ctn {
                background: var(--ci-table-toolbar-bg) !important;
            }

            .ci-trade-discount-page .ci-table-shell .fi-ta-header-toolbar {
                background: var(--ci-table-toolbar-bg) !important;
                border-color: var(--ci-line) !important;
            }

            .ci-trade-discount-page .ci-table-shell .fi-ta-content {
                background: var(--ci-table-content-bg) !important;
                border-color: var(--ci-line) !important;
            }

            .ci-trade-discount-page .ci-table-shell .fi-ta-record {
                background: var(--ci-table-row-bg) !important;
            }

            .ci-trade-discount-page .ci-table-shell .fi-ta-record:hover {
                background: var(--ci-table-row-hover-bg) !important;
            }

            .ci-trade-discount-page .ci-table-shell .fi-ta-pagination {
                background: var(--ci-table-pagination-bg) !important;
                border-color: var(--ci-line) !important;
            }

            .ci-trade-discount-page .ci-table-shell [class*='fi-table-header-cell'] {
                color: #e8f7fa !important;
                background: var(--ci-teal-muted-bg) !important;
                text-align: center !important;
            }

            .ci-trade-discount-page .ci-table-shell .fi-ta-text,
            .ci-trade-discount-page .ci-table-shell .fi-ta-text-item {
                color: rgba(255, 255, 255, 0.88) !important;
            }

            .ci-trade-discount-page .ci-table-shell .fi-ac-btn {
                border-radius: 9999px !important;
                min-width: 2.25rem;
                min-height: 2.25rem;
            }

            .ci-trade-discount-page .ci-table-shell .fi-ac-btn-color-primary {
                background: var(--ci-teal-bright) !important;
                color: var(--ci-on-accent) !important;
            }

            .ci-trade-discount-page .ci-table-shell .fi-ac-btn-color-danger {
                background: rgba(0, 188, 212, 0.22) !important;
                color: #e0f7fa !important;
                border: 1px solid rgba(0, 188, 212, 0.45) !important;
            }
        </style>

        <div class="ci-banner">
            <div class="ci-banner__main">
                <div class="ci-banner__icon" aria-hidden="true">
                    <x-filament::icon icon="heroicon-o-tag" class="h-8 w-8" />
                </div>
                <div class="min-w-0">
                    <div class="ci-banner__title">خصم تجاري</div>
                    <div class="ci-banner__sub">
                        تعريف أنواع الخصم التجاري ونسبها وربطها بمجموعة الحسابات
                    </div>
                </div>
            </div>
        </div>

        <div class="ci-card ci-form-inner ci-trade-discount-page">
            <div class="ci-card__head ci-card__head-trade">
                <x-filament::icon icon="heroicon-o-table-cells" class="h-5 w-5" />
                <h2>الخصومات التجارية</h2>
            </div>

            <div class="ci-table-shell">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
