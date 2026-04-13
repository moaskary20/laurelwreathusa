<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-add-asset-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        dir="rtl"
    >
        @include('filament.partials.wajebaty-theme-styles')

        <style>
            .ci-add-asset-page .ci-add-title {
                text-align: center;
                font-size: 1.25rem;
                font-weight: 700;
                color: rgba(255, 255, 255, 0.95);
                margin-bottom: 1.25rem;
            }

            .ci-add-asset-page .ci-add-card {
                border-radius: 0.75rem;
                border: 1px solid var(--ci-line);
                background: rgba(15, 18, 24, 0.65);
                padding: 1.5rem 1.75rem;
                max-width: 36rem;
                margin-inline: auto;
            }

            .ci-add-asset-page .fi-fo-field-wrp label {
                color: rgba(255, 255, 255, 0.88);
            }

            .ci-add-asset-page .fi-input-wrp,
            .ci-add-asset-page .fi-select-input {
                text-align: right;
            }
        </style>

        <div class="ci-add-title">إضافة</div>

        <div class="ci-add-card">
            <x-filament-panels::form id="add-asset-form" wire:submit="save">
                {{ $this->form }}

                <div class="mt-6 flex justify-end">
                    <x-filament-panels::form.actions
                        :actions="$this->getCachedFormActions()"
                        :full-width="$this->hasFullWidthFormActions()"
                    />
                </div>
            </x-filament-panels::form>
        </div>
    </div>
</x-filament-panels::page>
