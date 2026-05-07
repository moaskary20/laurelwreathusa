<x-filament-panels::page>
    <div class="ci-wajebaty ci-payroll-wajebaty ci-reports-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8" dir="rtl">
        @include('filament.partials.wajebaty-theme-styles')
        @include('filament.partials.payroll-wajebaty-styles')
        @include('filament.partials.reports-wajebaty-styles')

        <div class="ci-rep-title">اعمار الذمم</div>

        <div class="ci-card ci-form-inner ci-reports-card ci-rep-no-print">
            <div class="ci-rep-filters">
                <div class="ci-rep-field">
                    <label for="aging-as-of">كما في :</label>
                    <input id="aging-as-of" type="date" wire:model="asOfDate" />
                </div>
                <div class="ci-rep-field">
                    <label for="aging-party-type">نوع الذمم :</label>
                    <select id="aging-party-type" wire:model="partyType">
                        <option value="customers">عملاء</option>
                        <option value="suppliers">موردين</option>
                        <option value="both">عملاء وموردين</option>
                    </select>
                </div>
                <x-filament::button type="button" wire:click="search" icon="heroicon-o-magnifying-glass" color="warning">
                    بحث
                </x-filament::button>
            </div>
        </div>

        <div class="ci-card ci-form-inner ci-reports-card">
            <div class="ci-rep-toolbar ci-rep-no-print">
                <x-filament::button color="success" icon="heroicon-o-printer" wire:click="printReport">
                    طباعه
                </x-filament::button>
                <x-filament::button type="button" wire:click="exportToExcel" color="warning" icon="heroicon-o-arrow-down-tray">
                    إصدار إلى إكسل
                </x-filament::button>
            </div>

            <div class="ci-rep-report-head">
                <h3>اعمار الذمم</h3>
                <p class="ci-rep-co">{{ $this->companyDisplayName() }}</p>
                <p class="ci-rep-period">{{ $this->asOfLabel() }}</p>
            </div>

            <div class="ci-rep-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>النوع</th>
                            <th>الاسم</th>
                            <th class="ci-rep-num">0-30</th>
                            <th class="ci-rep-num">31-60</th>
                            <th class="ci-rep-num">61-90</th>
                            <th class="ci-rep-num">91-120</th>
                            <th class="ci-rep-num">أكثر من 120</th>
                            <th class="ci-rep-num">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (! $hasSearched)
                            <tr>
                                <td colspan="8" class="ci-rep-empty">اضغط «بحث» لعرض أعمار الذمم.</td>
                            </tr>
                        @elseif (count($rows) === 0)
                            <tr>
                                <td colspan="8" class="ci-rep-empty">لا توجد أرصدة مفتوحة حتى التاريخ المحدد.</td>
                            </tr>
                        @else
                            @foreach ($rows as $row)
                                <tr>
                                    <td>{{ $row['type'] }}</td>
                                    <td class="ci-rep-text">{{ $row['name'] }}</td>
                                    <td class="ci-rep-num">{{ number_format($row['current'], 2) }}</td>
                                    <td class="ci-rep-num">{{ number_format($row['d30'], 2) }}</td>
                                    <td class="ci-rep-num">{{ number_format($row['d60'], 2) }}</td>
                                    <td class="ci-rep-num">{{ number_format($row['d90'], 2) }}</td>
                                    <td class="ci-rep-num">{{ number_format($row['over90'], 2) }}</td>
                                    <td class="ci-rep-num">{{ number_format($row['total'], 2) }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                    @if ($hasSearched)
                        <tfoot>
                            <tr>
                                <td colspan="2">الإجمالي</td>
                                <td class="ci-rep-num">{{ number_format($totals['current'], 2) }}</td>
                                <td class="ci-rep-num">{{ number_format($totals['d30'], 2) }}</td>
                                <td class="ci-rep-num">{{ number_format($totals['d60'], 2) }}</td>
                                <td class="ci-rep-num">{{ number_format($totals['d90'], 2) }}</td>
                                <td class="ci-rep-num">{{ number_format($totals['over90'], 2) }}</td>
                                <td class="ci-rep-num">{{ number_format($totals['total'], 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
