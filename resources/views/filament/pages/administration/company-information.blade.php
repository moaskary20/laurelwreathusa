<x-filament-panels::page>
    <div
        class="ci-wajebaty -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        wire:key="company-info-{{ $this->tenant->getKey() }}"
    >
        @include('filament.partials.wajebaty-theme-styles')

        <div class="ci-banner">
            <div class="ci-banner__main">
                <div class="ci-banner__icon" aria-hidden="true">
                    <x-filament::icon icon="heroicon-o-building-office-2" class="h-8 w-8" />
                </div>
                <div class="min-w-0">
                    <div class="ci-banner__title">معلومات الشركة</div>
                    <div class="ci-banner__sub">
                        عرض وتعديل بيانات الشركة الحالية ({{ $this->tenant->trade_name }})
                    </div>
                </div>
            </div>
            <div class="ci-stats">
                <div class="ci-stat ci-stat--accent">
                    <div class="ci-stat__val">{{ $this->officesCount }}</div>
                    <div class="ci-stat__lbl">المكاتب</div>
                </div>
                <div class="ci-stat">
                    <div class="ci-stat__val">{{ $this->usersCount }}</div>
                    <div class="ci-stat__lbl">المستخدمون</div>
                </div>
                <div class="ci-stat">
                    <div class="ci-stat__val">#{{ $this->tenant->getKey() }}</div>
                    <div class="ci-stat__lbl">الشركة</div>
                </div>
            </div>
        </div>

        <div class="ci-grid">
            <div class="ci-card">
                <div class="ci-card__head">
                    <x-filament::icon icon="heroicon-o-map-pin" class="h-5 w-5" />
                    <h2>مكاتب الشركة</h2>
                </div>
                <div class="ci-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>المكتب</th>
                                <th>العنوان</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->tenant->offices as $office)
                                <tr>
                                    <td>{{ $office->name_ar ?? '—' }}</td>
                                    <td>{{ $office->address ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="ci-empty" colspan="2">لا يوجد بيانات</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="ci-card ci-form-inner">
                <div class="ci-card__head">
                    <x-filament::icon icon="heroicon-o-pencil-square" class="h-5 w-5" />
                    <h2>بيانات الشركة</h2>
                </div>

                <x-filament-panels::form id="company-information-form" wire:submit="save">
                    {{ $this->form }}

                    <div class="ci-actions">
                        <button
                            class="ci-btn-cancel"
                            type="button"
                            wire:click="cancel"
                        >
                            <x-filament::icon icon="heroicon-o-x-circle" class="h-5 w-5" />
                            إلغاء
                        </button>
                        <button class="ci-btn-save" type="submit">
                            <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-5 w-5" />
                            حفظ
                        </button>
                    </div>
                </x-filament-panels::form>
            </div>
        </div>
    </div>
</x-filament-panels::page>
