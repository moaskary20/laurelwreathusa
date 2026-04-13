<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-payroll-wajebaty ci-payroll-statement-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        wire:key="payroll-statement-{{ \Filament\Facades\Filament::getTenant()?->getKey() }}"
        dir="rtl"
    >
        @include('filament.partials.wajebaty-theme-styles')
        @include('filament.partials.payroll-wajebaty-styles')

        <div class="ci-banner">
            <div class="ci-banner__main">
                <div class="ci-banner__icon" aria-hidden="true">
                    <x-filament::icon icon="heroicon-o-calculator" class="h-8 w-8" />
                </div>
                <div class="min-w-0">
                    <div class="ci-banner__title">كشف الرواتب</div>
                    <div class="ci-banner__sub">الراتب الأساسي، العلاوات، الاقتطاعات، والضمان الاجتماعي</div>
                </div>
            </div>
        </div>

        <div class="ci-card ci-form-inner ci-payroll-statement-page">
            <div class="ci-card__head ci-card__head-pay">
                <x-filament::icon icon="heroicon-o-table-cells" class="h-5 w-5" />
                <h2>تفاصيل الرواتب</h2>
            </div>

            <div class="ci-table-shell ci-table-shell--scroll">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
