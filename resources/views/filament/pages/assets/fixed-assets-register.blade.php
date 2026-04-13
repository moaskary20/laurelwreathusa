<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-fixed-assets-register-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        wire:key="fixed-assets-register-{{ \Filament\Facades\Filament::getTenant()?->getKey() }}"
        dir="rtl"
    >
        @include('filament.partials.wajebaty-theme-styles')

        <style>
            .ci-fixed-assets-register-page .ci-far-title {
                font-size: 1.15rem;
                font-weight: 700;
                color: rgba(255, 255, 255, 0.95);
                margin-bottom: 1rem;
                text-align: right;
            }

            .ci-fixed-assets-register-page .ci-far-filters {
                border-radius: 0.75rem;
                border: 1px solid var(--ci-line);
                background: rgba(15, 18, 24, 0.65);
                padding: 1.25rem 1.5rem;
                margin-bottom: 1rem;
            }

            .ci-fixed-assets-register-page .ci-table-shell {
                overflow-x: auto;
            }

            .ci-fixed-assets-register-page .ci-table-shell .fi-ta {
                border-radius: 0.75rem;
                border: 1px solid var(--ci-line);
                min-width: 72rem;
            }

            .ci-fixed-assets-register-page .ci-table-shell [class*='fi-table-header-cell'] {
                color: #e8f7fa !important;
                background: rgba(0, 139, 163, 0.2) !important;
                text-align: center !important;
                white-space: nowrap;
            }

            .ci-fixed-assets-register-page .ci-table-shell .fi-ta-text,
            .ci-fixed-assets-register-page .ci-table-shell .fi-ta-text-item {
                color: rgba(255, 255, 255, 0.88) !important;
            }
        </style>

        <div class="ci-far-title">سجل الموجودات الثابته</div>

        <div class="ci-far-filters">
            {{ $this->form }}
        </div>

        <div class="ci-table-shell">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
