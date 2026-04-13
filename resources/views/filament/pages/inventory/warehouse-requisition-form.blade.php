<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-inventory-gov-wajebaty ci-warehouse-requisition-form-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        dir="rtl"
    >
        @include('filament.partials.wajebaty-theme-styles')
        @include('filament.partials.inventory-gov-wajebaty-styles')

        <div class="ci-gov-title">طلب صرف مستودع</div>

        <div class="ci-gov-box ci-form-inner">
            <x-filament-panels::form id="warehouse-requisition-form" wire:submit="save">
                {{ $this->form }}

                <x-filament-panels::form.actions
                    :actions="$this->getCachedFormActions()"
                    :full-width="$this->hasFullWidthFormActions()"
                />
            </x-filament-panels::form>
        </div>
    </div>
</x-filament-panels::page>
