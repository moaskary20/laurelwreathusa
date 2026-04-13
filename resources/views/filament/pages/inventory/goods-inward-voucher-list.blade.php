<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-inventory-gov-wajebaty ci-goods-inward-voucher-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        wire:key="goods-inward-voucher-{{ \Filament\Facades\Filament::getTenant()?->getKey() }}"
        dir="rtl"
    >
        @include('filament.partials.wajebaty-theme-styles')
        @include('filament.partials.inventory-gov-wajebaty-styles')

        <div class="ci-banner">
            <div class="ci-banner__main">
                <div class="ci-banner__icon" aria-hidden="true">
                    <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-8 w-8" />
                </div>
                <div class="min-w-0">
                    <div class="ci-banner__title">سند ادخال المواد</div>
                    <div class="ci-banner__sub">سندات إدخال المواد للمستودع</div>
                </div>
            </div>
        </div>

        <div class="ci-card ci-form-inner ci-goods-inward-voucher-page">
            <div class="ci-card__head ci-card__head-gov">
                <x-filament::icon icon="heroicon-o-table-cells" class="h-5 w-5" />
                <h2>سندات الإدخال</h2>
            </div>

            <div class="ci-table-shell">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
