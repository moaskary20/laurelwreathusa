<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-disposal-reports-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        wire:key="disposal-reports-{{ \Filament\Facades\Filament::getTenant()?->getKey() }}"
        dir="rtl"
    >
        @include('filament.partials.wajebaty-theme-styles')

        <style>
            .ci-disposal-reports-page .ci-dr-title {
                text-align: center;
                font-size: 1.35rem;
                font-weight: 700;
                color: rgba(255, 255, 255, 0.96);
                margin-bottom: 1.25rem;
            }

            .ci-disposal-reports-page .ci-dr-toolbar {
                display: flex;
                flex-wrap: wrap;
                align-items: flex-start;
                justify-content: space-between;
                gap: 1rem;
                margin-bottom: 1.25rem;
                padding-bottom: 1rem;
                border-bottom: 1px solid var(--ci-line);
            }

            .ci-disposal-reports-page .ci-dr-actions {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
                align-items: flex-start;
            }

            .ci-disposal-reports-page .ci-dr-search {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                align-items: center;
            }

            .ci-disposal-reports-page .ci-dr-search input {
                border-radius: 9999px;
                border: 1px solid var(--ci-line);
                background: rgba(15, 18, 24, 0.65);
                color: rgba(255, 255, 255, 0.9);
                padding: 0.5rem 1rem;
                min-width: 12rem;
            }

            .ci-disposal-reports-page .ci-dr-search input::placeholder {
                color: rgba(255, 255, 255, 0.45);
            }

            .ci-disposal-reports-page .ci-table-shell {
                overflow-x: auto;
            }

            .ci-disposal-reports-page .ci-table-shell .fi-ta {
                border-radius: 0.75rem;
                border: 1px solid var(--ci-line);
                min-width: 56rem;
            }

            .ci-disposal-reports-page .ci-table-shell [class*='fi-table-header-cell'] {
                color: #e8f7fa !important;
                background: rgba(0, 139, 163, 0.2) !important;
                text-align: center !important;
            }

            .ci-disposal-reports-page .ci-table-shell .fi-ta-text,
            .ci-disposal-reports-page .ci-table-shell .fi-ta-text-item {
                color: rgba(255, 255, 255, 0.88) !important;
            }

            @media print {
                .fi-sidebar,
                .fi-topbar,
                .fi-header,
                .ci-dr-toolbar {
                    display: none !important;
                }
            }
        </style>

        <div class="ci-dr-title">تقارير الاستبعاد</div>

        <div class="ci-dr-toolbar">
            <div class="ci-dr-actions">
                <x-filament::button
                    color="success"
                    icon="heroicon-o-printer"
                    wire:click="printReport"
                >
                    طباعه
                </x-filament::button>
                <x-filament::button
                    color="gray"
                    wire:click="exportToExcel"
                >
                    اصدار الى اكسل
                </x-filament::button>
            </div>

            <div class="ci-dr-search">
                <input
                    type="text"
                    wire:model="reportYear"
                    placeholder="البحث عن طريق السنة"
                    inputmode="numeric"
                    maxlength="4"
                />
                <x-filament::button
                    color="gray"
                    icon="heroicon-o-magnifying-glass"
                    wire:click="searchByYear"
                >
                    بحث
                </x-filament::button>
            </div>
        </div>

        <div class="ci-table-shell">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
