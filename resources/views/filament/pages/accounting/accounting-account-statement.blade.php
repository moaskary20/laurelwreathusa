<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-accounting-statement-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        wire:key="accounting-statement-{{ \Filament\Facades\Filament::getTenant()?->getKey() }}"
        dir="rtl"
    >
        @include('filament.partials.wajebaty-theme-styles')

        <style>
            .ci-accounting-statement-page .ci-statement-filter-card {
                border-radius: 0.75rem;
                border: 1px solid var(--ci-line);
                background: var(--ci-table-content-bg);
                padding: 1.25rem 1.5rem;
                margin-bottom: 1.25rem;
            }

            .ci-accounting-statement-page .ci-statement-filter-title {
                font-size: 1.1rem;
                font-weight: 700;
                color: rgba(255, 255, 255, 0.95);
                margin-bottom: 1rem;
            }

            .ci-accounting-statement-page .ci-statement-account-banner {
                border-radius: 0.75rem;
                border: 1px solid var(--ci-line);
                background: var(--ci-table-row-bg);
                padding: 1rem 1.25rem;
                margin-bottom: 0.75rem;
            }

            .ci-accounting-statement-page .ci-statement-account-banner .ci-name {
                color: #f87171;
                font-weight: 700;
                font-size: 1.05rem;
            }

            .ci-accounting-statement-page .ci-table-shell .fi-ta {
                border-radius: 0.75rem;
                border: 1px solid var(--ci-line);
                overflow: hidden;
                background: var(--ci-surface-overlay);
            }

            .ci-accounting-statement-page .ci-table-shell .fi-ta-header-ctn {
                background: var(--ci-table-toolbar-bg) !important;
            }

            .ci-accounting-statement-page .ci-table-shell .fi-ta-header-toolbar {
                background: var(--ci-table-toolbar-bg) !important;
                border-color: var(--ci-line) !important;
            }

            .ci-accounting-statement-page .ci-table-shell .fi-ta-content {
                background: var(--ci-table-content-bg) !important;
                border-color: var(--ci-line) !important;
            }

            .ci-accounting-statement-page .ci-table-shell .fi-ta-record {
                background: var(--ci-table-row-bg) !important;
            }

            .ci-accounting-statement-page .ci-table-shell .fi-ta-record:hover {
                background: var(--ci-table-row-hover-bg) !important;
            }

            .ci-accounting-statement-page .ci-table-shell [class*='fi-table-header-cell'] {
                color: #e8f7fa !important;
                background: var(--ci-teal-muted-bg) !important;
                text-align: center !important;
            }

            .ci-accounting-statement-page .ci-table-shell .fi-ta-text,
            .ci-accounting-statement-page .ci-table-shell .fi-ta-text-item {
                color: rgba(255, 255, 255, 0.88) !important;
            }

            @media print {
                .fi-sidebar,
                .fi-topbar,
                .fi-header,
                .ci-statement-filter-card,
                .ci-print-hide {
                    display: none !important;
                }

                .ci-accounting-statement-page {
                    padding: 0 !important;
                }
            }
        </style>

        <div class="ci-statement-filter-card print:hidden">
            <div class="ci-statement-filter-title">كشف حساب</div>
            <form wire:submit="searchStatement" class="space-y-4">
                {{ $this->form }}
                <div class="flex justify-start">
                    <x-filament::button type="submit" icon="heroicon-m-magnifying-glass">
                        بحث
                    </x-filament::button>
                </div>
            </form>
        </div>

        @if ($this->statementReady)
            <div class="ci-statement-account-banner print:hidden">
                <div class="flex flex-wrap items-center justify-between gap-4 text-sm text-white/90">
                    <div>
                        <span class="opacity-80">اسم الحساب :</span>
                        <span class="ci-name">{{ $this->selectedAccountName }}</span>
                    </div>
                    <div>
                        <span class="opacity-80">الكود :</span>
                        <span class="font-semibold text-white">{{ $this->selectedAccountCode }}</span>
                    </div>
                </div>
                <div class="mt-3 text-sm text-white/85">
                    <span class="opacity-80">الفترة من:</span>
                    <span class="font-medium text-white">{{ \Carbon\Carbon::parse($this->filterData['date_from'])->format('Y/m/d') }}</span>
                    <span class="mx-1 opacity-80">إلى:</span>
                    <span class="font-medium text-white">{{ \Carbon\Carbon::parse($this->filterData['date_to'])->format('Y/m/d') }}</span>
                </div>
            </div>

            <div class="mb-3 flex justify-end print:hidden ci-print-hide">
                <x-filament::button
                    type="button"
                    color="success"
                    icon="heroicon-o-printer"
                    onclick="window.print()"
                >
                    طباعه
                </x-filament::button>
            </div>
        @endif

        <div class="ci-card ci-form-inner ci-accounting-statement-page">
            <div class="ci-table-shell">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
