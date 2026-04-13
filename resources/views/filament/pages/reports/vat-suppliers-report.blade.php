<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-payroll-wajebaty ci-reports-page ci-vat-suppliers-report-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        dir="rtl"
        wire:key="vat-suppliers-report-{{ \Filament\Facades\Filament::getTenant()?->getKey() }}"
    >
        @include('filament.partials.wajebaty-theme-styles')
        @include('filament.partials.payroll-wajebaty-styles')
        @include('filament.partials.reports-wajebaty-styles')

        <div class="ci-rep-title">ضريبة القيمة المضافة للموردين</div>

        <div class="ci-card ci-form-inner ci-reports-card ci-rep-no-print">
            <div class="ci-rep-filters">
                <div class="ci-rep-field">
                    <label for="vat-sup-select">اسم المورد</label>
                    <select id="vat-sup-select" wire:model.live="supplierId">
                        <option value="">اختيار مورد — الكل</option>
                        @foreach ($this->suppliersOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ci-rep-field">
                    <label for="vat-sup-from">التاريخ من:</label>
                    <input id="vat-sup-from" type="date" wire:model="dateFrom" />
                </div>
                <div class="ci-rep-field">
                    <label for="vat-sup-to">إلى:</label>
                    <input id="vat-sup-to" type="date" wire:model="dateTo" />
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

            <div class="ci-rep-meta">
                <div><span>اسم المورد :</span> {{ $this->headerSupplierLabel() }}</div>
                <div><span>الرقم الضريبي :</span> {{ $this->headerTaxNumberLabel() }}</div>
            </div>

            <p class="ci-rep-co">{{ $this->companyDisplayName() }}</p>

            <div class="ci-rep-table-wrap">
                <table>
                    <thead>
                        <tr>
                            @if ($supplierId === '' || $supplierId === null)
                                <th>المورد</th>
                            @endif
                            <th>التاريخ</th>
                            <th>رقم الفاتورة</th>
                            <th class="ci-rep-num">المبلغ قبل الضريبة</th>
                            <th class="ci-rep-num">الضريبة</th>
                            <th class="ci-rep-num">المبلغ بعد الضريبة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (! $hasSearched)
                            <tr>
                                <td
                                    colspan="{{ ($supplierId === '' || $supplierId === null) ? 6 : 5 }}"
                                    class="ci-rep-empty"
                                >
                                    اضغط «بحث» لعرض الفواتير في هذه الفترة.
                                </td>
                            </tr>
                        @elseif (count($reportRows) === 0)
                            <tr>
                                <td
                                    colspan="{{ ($supplierId === '' || $supplierId === null) ? 6 : 5 }}"
                                    class="ci-rep-empty"
                                >
                                    لا توجد فواتير ضريبية في هذه الفترة.
                                </td>
                            </tr>
                        @else
                            @foreach ($reportRows as $row)
                                <tr>
                                    @if ($supplierId === '' || $supplierId === null)
                                        <td class="ci-rep-text">{{ $row['supplier_name'] ?? '—' }}</td>
                                    @endif
                                    <td>{{ $row['invoice_date'] }}</td>
                                    <td>{{ $row['invoice_number'] }}</td>
                                    <td class="ci-rep-num">{{ number_format($row['before_tax'], 2) }}</td>
                                    <td class="ci-rep-num">{{ number_format($row['tax'], 2) }}</td>
                                    <td class="ci-rep-num">{{ number_format($row['after_tax'], 2) }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
