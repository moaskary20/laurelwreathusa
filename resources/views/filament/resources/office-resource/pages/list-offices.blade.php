<x-filament-panels::page
    @class([
        'fi-resource-list-records-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
    ])
>
    <div
        class="ci-wajebaty -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        wire:key="list-offices"
    >
        @include('filament.partials.wajebaty-theme-styles')

        <div class="ci-banner">
            <div class="ci-banner__main">
                <div class="ci-banner__icon" aria-hidden="true">
                    <x-filament::icon icon="heroicon-o-squares-plus" class="h-8 w-8" />
                </div>
                <div class="min-w-0">
                    <div class="ci-banner__title">{{ $this->getTitle() }}</div>
                    <div class="ci-banner__sub">
                        إدارة المكاتب التابعة للشركة الحالية
                        @if (\Filament\Facades\Filament::getTenant())
                            <span class="opacity-90">({{ \Filament\Facades\Filament::getTenant()->trade_name }})</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="ci-banner__actions">
                <x-filament::actions :actions="$this->getCachedHeaderActions()" />
            </div>
        </div>

        <div class="flex flex-col gap-y-6 ci-table-shell">
            <x-filament-panels::resources.tabs />

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE, scopes: $this->getRenderHookScopes()) }}

            {{ $this->table }}

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER, scopes: $this->getRenderHookScopes()) }}
        </div>
    </div>
</x-filament-panels::page>
