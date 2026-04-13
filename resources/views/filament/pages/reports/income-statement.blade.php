<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-payroll-wajebaty ci-reports-page ci-income-statement-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        dir="rtl"
        wire:key="income-statement-{{ \Filament\Facades\Filament::getTenant()?->getKey() }}"
    >
        @include('filament.partials.wajebaty-theme-styles')
        @include('filament.partials.payroll-wajebaty-styles')
        @include('filament.partials.reports-wajebaty-styles')

        <div class="ci-rep-title">قائمة الدخل</div>

        <div class="ci-card ci-form-inner ci-reports-card ci-rep-no-print">
            <div class="ci-rep-filters">
                <div class="ci-rep-field">
                    <label for="income-date-from">الفترة من :</label>
                    <input id="income-date-from" type="date" wire:model="dateFrom" />
                </div>
                <div class="ci-rep-field">
                    <label for="income-date-to">إلى :</label>
                    <input id="income-date-to" type="date" wire:model="dateTo" />
                </div>
                <x-filament::button
                    type="button"
                    wire:click="search"
                    icon="heroicon-o-magnifying-glass"
                    color="warning"
                >
                    بحث
                </x-filament::button>
            </div>
        </div>

        <div class="ci-card ci-form-inner ci-reports-card">
            <div class="ci-rep-toolbar ci-rep-no-print">
                <x-filament::button
                    color="success"
                    icon="heroicon-o-printer"
                    wire:click="printReport"
                >
                    طباعه
                </x-filament::button>
                <x-filament::button type="button" wire:click="exportToExcel" color="warning" icon="heroicon-o-arrow-down-tray">
                    إصدار إلى إكسل
                </x-filament::button>
            </div>

            <div class="ci-rep-report-head">
                <h3>قائمة الدخل</h3>
                <p class="ci-rep-co">{{ $this->companyDisplayName() }}</p>
                <p class="ci-rep-period">{{ $this->periodLabel() }}</p>
            </div>

            <div class="ci-rep-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>اسم الحساب</th>
                            <th class="ci-rep-col-bal">الرصيد</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (! $hasSearched)
                            <tr>
                                <td colspan="2" class="ci-rep-empty">اضغط «بحث» لعرض التقرير لهذه الفترة.</td>
                            </tr>
                        @else
                            @foreach ($reportRows as $row)
                                @if ($row['kind'] === 'section')
                                    <tr class="ci-rep-section">
                                        <td colspan="2">{{ $row['label'] }}</td>
                                    </tr>
                                @elseif ($row['kind'] === 'muted')
                                    <tr class="ci-rep-muted">
                                        <td colspan="2">{{ $row['label'] }}</td>
                                    </tr>
                                @else
                                    <tr @class([
                                        'ci-rep-subtotal' => $row['kind'] === 'subtotal',
                                        'ci-rep-net' => $row['kind'] === 'net',
                                    ])>
                                        <td>{{ $row['label'] }}</td>
                                        <td class="ci-rep-num">
                                            @if ($row['balance'] !== null)
                                                {{ number_format((float) $row['balance'], 2) }}
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
