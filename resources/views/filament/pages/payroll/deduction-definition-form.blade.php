<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-payroll-wajebaty ci-deduction-definition-form-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        dir="rtl"
    >
        @include('filament.partials.wajebaty-theme-styles')
        @include('filament.partials.payroll-wajebaty-styles')

        <style>
            @media print {
                .fi-sidebar,
                .fi-topbar,
                .fi-header {
                    display: none !important;
                }
            }
        </style>

        <div class="ci-card ci-form-inner ci-payroll-form-card">
            <h2 class="ci-payroll-form-title">{{ $this->getTitle() }}</h2>

            <x-filament-panels::form id="deduction-definition-form" wire:submit="save">
                {{ $this->form }}

                <x-filament-panels::form.actions
                    :actions="$this->getCachedFormActions()"
                    :full-width="$this->hasFullWidthFormActions()"
                />
            </x-filament-panels::form>
        </div>
    </div>
</x-filament-panels::page>
