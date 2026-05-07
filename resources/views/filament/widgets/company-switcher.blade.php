<x-filament-widgets::widget>
    <x-filament::section>

        {{-- ───── عنوان البطاقة ───── --}}
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-building-office-2 class="h-5 w-5 text-primary-500" />
                <span>تبديل الشركة</span>
            </div>
        </x-slot>

        <x-slot name="description">
            اختر الشركة التي تريد إدارتها. ستُحدَّث جميع البيانات في لوحة التحكم وفقاً للشركة المختارة.
        </x-slot>

        {{-- ───── حالة الشركة النشطة ───── --}}
        @php
            $currentTenant = filament()->getTenant();
        @endphp

        @if ($currentTenant)
            <div class="mb-4 flex items-center gap-2 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-800 dark:bg-success-950 dark:text-success-300">
                <x-heroicon-o-check-circle class="h-5 w-5 shrink-0" />
                <span>
                    الشركة النشطة حالياً:
                    <strong>{{ $currentTenant->trade_name ?: $currentTenant->legal_name }}</strong>
                </span>
            </div>
        @endif

        {{-- ───── اختيار الشركة ───── --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">

            <div class="flex-1">
                <label for="cs-company-select"
                       class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    اختر شركة للتبديل إليها
                </label>

                <select id="cs-company-select"
                        wire:model="selectedCompanyId"
                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900
                               shadow-sm transition
                               focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500
                               dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                    <option value="">-- اختر شركة --</option>
                    @foreach ($this->getCompanies() as $company)
                        <option value="{{ $company->id }}"
                                {{ (string) $company->id === $selectedCompanyId ? 'selected' : '' }}>
                            {{ $company->trade_name ?: $company->legal_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <x-filament::button
                wire:click="switchCompany"
                size="lg"
                icon="heroicon-o-arrow-path"
                class="shrink-0">
                تبديل الشركة
            </x-filament::button>
        </div>

    </x-filament::section>
</x-filament-widgets::widget>
