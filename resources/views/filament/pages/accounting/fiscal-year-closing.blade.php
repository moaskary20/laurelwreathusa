<x-filament-panels::page>
    <div class="ci-wajebaty ci-payroll-wajebaty ci-reports-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8" dir="rtl">
        @include('filament.partials.wajebaty-theme-styles')
        @include('filament.partials.payroll-wajebaty-styles')
        @include('filament.partials.reports-wajebaty-styles')

        <div class="ci-rep-title">إغلاق السنة المالية</div>

        <div class="ci-card ci-form-inner ci-reports-card">
            <div class="ci-rep-report-head">
                <h3>{{ $this->companyDisplayName() }}</h3>
                <p class="ci-rep-period">{{ $this->periodLabel() }}</p>
            </div>

            <div class="ci-rep-filters">
                <div class="ci-rep-field">
                    <label for="year-start">من تاريخ :</label>
                    <input id="year-start" type="date" wire:model.live="yearStart" wire:change="loadClosing" />
                </div>
                <div class="ci-rep-field">
                    <label for="year-end">إلى تاريخ :</label>
                    <input id="year-end" type="date" wire:model.live="yearEnd" wire:change="loadClosing" />
                </div>
                <div class="ci-rep-field">
                    <label>الحالة :</label>
                    <div style="min-width: 11rem; padding: .6rem .75rem; border: 1px solid rgba(255,255,255,.14); border-radius: .5rem; color: #fff;">
                        {{ $this->statusLabel() }}
                    </div>
                </div>
            </div>

            <div class="ci-rep-meta" style="margin-top: 1rem;">
                <div>آخر إغلاق: <span>{{ $closedAt ?: '—' }}</span></div>
                <div>آخر فتح: <span>{{ $openedAt ?: '—' }}</span></div>
            </div>

            <div class="ci-rep-field" style="margin-top: 1rem;">
                <label for="year-notes">ملاحظات :</label>
                <textarea id="year-notes" wire:model="notes" rows="4" style="width: 100%; border-radius: .5rem; border: 1px solid rgba(255,255,255,.14); background: rgba(255,255,255,.09); color: #fff; padding: .75rem;"></textarea>
            </div>

            <div class="ci-rep-toolbar" style="margin-top: 1rem; margin-bottom: 0; padding-bottom: 0; border-bottom: 0;">
                <x-filament::button type="button" color="danger" icon="heroicon-o-lock-closed" wire:click="closeYear">
                    إغلاق السنة المالية
                </x-filament::button>
                <x-filament::button type="button" color="success" icon="heroicon-o-lock-open" wire:click="openYear">
                    فتح السنة المالية
                </x-filament::button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
