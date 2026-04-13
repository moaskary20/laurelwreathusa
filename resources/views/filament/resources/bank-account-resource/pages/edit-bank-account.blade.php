<x-filament-panels::page
    @class([
        'fi-resource-edit-record-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
        'fi-resource-record-' . $record->getKey(),
    ])
>
    <div
        class="ci-wajebaty -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        wire:key="edit-bank-account-{{ $record->getKey() }}"
    >
        @include('filament.partials.wajebaty-theme-styles')

        <div class="ci-banner">
            <div class="ci-banner__main">
                <div class="ci-banner__icon" aria-hidden="true">
                    <x-filament::icon icon="heroicon-o-building-library" class="h-8 w-8" />
                </div>
                <div class="min-w-0">
                    <div class="ci-banner__title">{{ $this->getTitle() }}</div>
                    <div class="ci-banner__sub">{{ $this->getRecordTitle() }}</div>
                </div>
            </div>
            <div class="ci-banner__actions">
                <x-filament::actions :actions="$this->getCachedHeaderActions()" />
            </div>
        </div>

        <div class="ci-card ci-form-inner">
            <x-filament-panels::form
                id="form"
                :wire:key="$this->getId() . '.forms.' . $this->getFormStatePath()"
                wire:submit="save"
            >
                {{ $this->form }}

                <x-filament-panels::form.actions
                    :actions="$this->getCachedFormActions()"
                    :full-width="$this->hasFullWidthFormActions()"
                />
            </x-filament-panels::form>
        </div>
    </div>

    <x-filament-panels::page.unsaved-data-changes-alert />
</x-filament-panels::page>
