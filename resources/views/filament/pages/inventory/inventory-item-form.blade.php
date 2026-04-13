<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-inventory-item-form-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        dir="rtl"
    >
        @include('filament.partials.wajebaty-theme-styles')

        <style>
            .ci-inventory-item-form-page .ci-so-title {
                text-align: right;
                font-size: 1.15rem;
                font-weight: 700;
                color: rgba(255, 255, 255, 0.95);
                margin-bottom: 1.25rem;
                padding-bottom: 0.75rem;
                border-bottom: 1px solid var(--ci-line);
            }

            @media print {
                .fi-sidebar,
                .fi-topbar,
                .fi-header {
                    display: none !important;
                }
            }
        </style>

        <div class="ci-so-title">{{ $this->getTitle() }}</div>

        <x-filament-panels::form id="inventory-item-form" wire:submit="save">
            {{ $this->form }}

            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        </x-filament-panels::form>
    </div>
</x-filament-panels::page>
