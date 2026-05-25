<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-invoice-mgmt -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        wire:key="invoice-management-{{ \Filament\Facades\Filament::getTenant()?->getKey() }}"
    >
        @include('filament.partials.wajebaty-theme-styles')

        <style>
            .ci-invoice-mgmt .ci-card__head-invoice h2 {
                font-size: 1.15rem;
                font-weight: 700;
                color: #fff;
            }

            .ci-invoice-mgmt .ci-table-shell .fi-ta {
                border-radius: 0.75rem;
                border: 1px solid var(--ci-line);
                overflow: hidden;
                background: var(--ci-surface-overlay);
            }

            .ci-invoice-mgmt .ci-table-shell .fi-ta-header-ctn {
                background: var(--ci-table-toolbar-bg) !important;
            }

            .ci-invoice-mgmt .ci-table-shell .fi-ta-header-toolbar {
                background: var(--ci-table-toolbar-bg) !important;
                border-color: var(--ci-line) !important;
            }

            .ci-invoice-mgmt .ci-table-shell .fi-ta-content {
                background: var(--ci-table-content-bg) !important;
                border-color: var(--ci-line) !important;
            }

            .ci-invoice-mgmt .ci-table-shell .fi-ta-record {
                background: var(--ci-table-row-bg) !important;
            }

            .ci-invoice-mgmt .ci-table-shell .fi-ta-record:hover {
                background: var(--ci-table-row-hover-bg) !important;
            }

            .ci-invoice-mgmt .ci-table-shell .fi-ta-pagination {
                background: var(--ci-table-pagination-bg) !important;
                border-color: var(--ci-line) !important;
            }

            .ci-invoice-mgmt .ci-table-shell [class*='fi-table-header-cell'] {
                color: #e8f7fa !important;
                background: var(--ci-teal-muted-bg) !important;
            }

            .ci-invoice-mgmt .ci-table-shell .fi-ta-text,
            .ci-invoice-mgmt .ci-table-shell .fi-ta-text-item {
                color: rgba(255, 255, 255, 0.88) !important;
            }

            .ci-invoice-mgmt .ci-table-shell .fi-ac-btn {
                border-radius: 0.45rem !important;
            }

            /* Modal forms: boxed textareas */
            .fi-modal .ci-invoice-textarea,
            .fi-modal textarea.ci-invoice-textarea {
                border: 1px solid rgba(148, 163, 184, 0.45) !important;
                border-radius: 0.5rem !important;
            }
        </style>

        <div class="ci-banner">
            <div class="ci-banner__main">
                <div class="ci-banner__icon" aria-hidden="true">
                    <x-filament::icon icon="heroicon-o-document-text" class="h-8 w-8" />
                </div>
                <div class="min-w-0">
                    <div class="ci-banner__title">إدارة الفواتير</div>
                    <div class="ci-banner__sub">
                        إدارة النصوص الظاهرة على الفواتير — أضف نصاً جديداً أو عدّل أو احذف من الجدول
                    </div>
                </div>
            </div>
        </div>

        <div class="ci-card ci-form-inner ci-invoice-mgmt">
            <div class="ci-card__head ci-card__head-invoice">
                <x-filament::icon icon="heroicon-o-queue-list" class="h-5 w-5" />
                <h2>نصوص الفواتير</h2>
            </div>

            <div class="ci-table-shell">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
