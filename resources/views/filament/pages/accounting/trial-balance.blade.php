<x-filament-panels::page>
    <div class="ci-wajebaty ci-payroll-wajebaty ci-reports-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8" dir="rtl">
        @include('filament.partials.wajebaty-theme-styles')
        @include('filament.partials.payroll-wajebaty-styles')
        @include('filament.partials.reports-wajebaty-styles')

        <div class="ci-rep-title">ميزان المراجعة</div>

        <div class="ci-card ci-form-inner ci-reports-card ci-rep-no-print">
            <div class="ci-rep-filters">
                <div class="ci-rep-field">
                    <label for="trial-date-from">الفترة من :</label>
                    <input id="trial-date-from" type="date" wire:model="dateFrom" />
                </div>
                <div class="ci-rep-field">
                    <label for="trial-date-to">إلى :</label>
                    <input id="trial-date-to" type="date" wire:model="dateTo" />
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
                <h3>ميزان المراجعة</h3>
                <p class="ci-rep-co">{{ $this->companyDisplayName() }}</p>
                <p class="ci-rep-period">{{ $this->periodLabel() }}</p>
            </div>

            <div class="ci-rep-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>رقم الحساب</th>
                            <th>اسم الحساب</th>
                            <th class="ci-rep-num">مدين الحركة</th>
                            <th class="ci-rep-num">دائن الحركة</th>
                            <th class="ci-rep-num">رصيد مدين</th>
                            <th class="ci-rep-num">رصيد دائن</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (! $hasSearched)
                            <tr>
                                <td colspan="6" class="ci-rep-empty">اضغط «بحث» لعرض ميزان المراجعة.</td>
                            </tr>
                        @elseif (count($rows) === 0)
                            <tr>
                                <td colspan="6" class="ci-rep-empty">لا توجد حركات في الفترة المحددة.</td>
                            </tr>
                        @else
                            @foreach ($rows as $row)
                                <tr>
                                    <td>{{ $row['code'] ?: '—' }}</td>
                                    <td class="ci-rep-text">{{ $row['name'] }}</td>
                                    <td class="ci-rep-num">{{ number_format($row['debit'], 2) }}</td>
                                    <td class="ci-rep-num">{{ number_format($row['credit'], 2) }}</td>
                                    <td class="ci-rep-num">{{ number_format($row['balance_debit'], 2) }}</td>
                                    <td class="ci-rep-num">{{ number_format($row['balance_credit'], 2) }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                    @if ($hasSearched)
                        <tfoot>
                            <tr>
                                <td colspan="2">الإجمالي</td>
                                <td class="ci-rep-num">{{ number_format($totalDebit, 2) }}</td>
                                <td class="ci-rep-num">{{ number_format($totalCredit, 2) }}</td>
                                <td class="ci-rep-num">{{ number_format($totalBalanceDebit, 2) }}</td>
                                <td class="ci-rep-num">{{ number_format($totalBalanceCredit, 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
