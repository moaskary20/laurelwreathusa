<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-asset-disposal-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        dir="rtl"
    >
        @include('filament.partials.wajebaty-theme-styles')

        <style>
            .ci-asset-disposal-page .ci-disposal-title {
                text-align: center;
                font-size: 1.35rem;
                font-weight: 700;
                color: rgba(255, 255, 255, 0.96);
                margin-bottom: 1.5rem;
            }

            .ci-asset-disposal-page .ci-disposal-card {
                border-radius: 0.75rem;
                border: 1px solid var(--ci-line);
                background: rgba(15, 18, 24, 0.65);
                padding: 1.5rem 1.75rem;
                max-width: 32rem;
                margin-inline: auto;
            }

            .ci-asset-disposal-page .fi-fo-field-wrp label {
                color: rgba(255, 255, 255, 0.88);
            }
        </style>

        <div class="ci-disposal-title">إستبعاد</div>

        <div class="ci-disposal-card">
            <x-filament-panels::form wire:submit.prevent>
                {{ $this->form }}
            </x-filament-panels::form>
        </div>
    </div>
</x-filament-panels::page>
