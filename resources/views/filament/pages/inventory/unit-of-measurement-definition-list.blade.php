<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-unit-measurement-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        wire:key="unit-measurement-definition-{{ \Filament\Facades\Filament::getTenant()?->getKey() }}"
        dir="rtl"
    >
        @include('filament.partials.wajebaty-theme-styles')

        <style>
            .ci-unit-measurement-page .ci-card__head-um h2 {
                font-size: 1.15rem;
                font-weight: 700;
                color: #fff;
            }

            .ci-unit-measurement-page .ci-table-shell .fi-ta {
                border-radius: 0.75rem;
                border: 1px solid var(--ci-line);
                overflow: hidden;
                background: rgba(0, 0, 0, 0.28);
            }

            .ci-unit-measurement-page .ci-table-shell .fi-ta-header-ctn {
                background: rgba(26, 32, 41, 0.98) !important;
            }

            .ci-unit-measurement-page .ci-table-shell .fi-ta-header-toolbar {
                background: rgba(26, 32, 41, 0.98) !important;
                border-color: var(--ci-line) !important;
            }

            .ci-unit-measurement-page .ci-table-shell .fi-ta-content {
                background: rgba(15, 18, 24, 0.65) !important;
                border-color: var(--ci-line) !important;
            }

            .ci-unit-measurement-page .ci-table-shell .fi-ta-record {
                background: rgba(30, 37, 48, 0.92) !important;
            }

            .ci-unit-measurement-page .ci-table-shell .fi-ta-record:hover {
                background: rgba(40, 50, 64, 0.96) !important;
            }

            .ci-unit-measurement-page .ci-table-shell [class*='fi-table-header-cell'] {
                color: #e8f7fa !important;
                background: rgba(0, 139, 163, 0.2) !important;
                text-align: center !important;
            }

            .ci-unit-measurement-page .ci-table-shell .fi-ta-text,
            .ci-unit-measurement-page .ci-table-shell .fi-ta-text-item {
                color: rgba(255, 255, 255, 0.88) !important;
            }

            .ci-unit-measurement-page .ci-table-shell .fi-ac-btn {
                border-radius: 9999px !important;
                min-width: 2.25rem;
                min-height: 2.25rem;
            }

            .ci-unit-measurement-page .ci-table-shell .fi-ac-btn-color-primary {
                background: var(--ci-teal-bright) !important;
                color: #0b0e11 !important;
            }

            .ci-unit-measurement-page .ci-table-shell .fi-ac-btn-color-danger {
                background: rgba(0, 188, 212, 0.22) !important;
                color: #e0f7fa !important;
                border: 1px solid rgba(0, 188, 212, 0.45) !important;
            }
        </style>

        <div class="ci-banner">
            <div class="ci-banner__main">
                <div class="ci-banner__icon" aria-hidden="true">
                    <x-filament::icon icon="heroicon-o-scale" class="h-8 w-8" />
                </div>
                <div class="min-w-0">
                    <div class="ci-banner__title">تعريف وحدة القياس</div>
                    <div class="ci-banner__sub">تعريف وحدات القياس المستخدمة في المخزون والأصناف</div>
                </div>
            </div>
        </div>

        <div class="ci-card ci-form-inner ci-unit-measurement-page">
            <div class="ci-card__head ci-card__head-um">
                <x-filament::icon icon="heroicon-o-table-cells" class="h-5 w-5" />
                <h2>وحدات القياس</h2>
            </div>

            <div class="ci-table-shell">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
