<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-goods-outward-voucher-form-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        dir="rtl"
    >
        @include('filament.partials.wajebaty-theme-styles')

        <style>
            .ci-goods-outward-voucher-form-page .ci-gov-title {
                text-align: center;
                font-size: 1.2rem;
                font-weight: 700;
                color: rgba(255, 255, 255, 0.95);
                margin-bottom: 1.25rem;
            }

            .ci-goods-outward-voucher-form-page .ci-gov-box {
                border-radius: 0.75rem;
                border: 1px solid var(--ci-line);
                background: rgba(15, 18, 24, 0.65);
                padding: 1.25rem 1.5rem;
                margin-bottom: 1rem;
            }

            @media print {
                .fi-sidebar,
                .fi-topbar,
                .fi-header {
                    display: none !important;
                }
            }
        </style>

        <div class="ci-gov-title">سند اخراج بضاعه</div>

        <div class="ci-gov-box">
            <x-filament-panels::form id="goods-outward-voucher-form" wire:submit="save">
                {{ $this->form }}

                <x-filament-panels::form.actions
                    :actions="$this->getCachedFormActions()"
                    :full-width="$this->hasFullWidthFormActions()"
                />
            </x-filament-panels::form>
        </div>
    </div>
</x-filament-panels::page>
