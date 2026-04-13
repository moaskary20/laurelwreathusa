<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-payroll-wajebaty ci-reports-page ci-revenue-by-customer-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        dir="rtl"
        wire:key="revenue-by-customer-{{ \Filament\Facades\Filament::getTenant()?->getKey() }}"
    >
        @include('filament.partials.wajebaty-theme-styles')
        @include('filament.partials.payroll-wajebaty-styles')
        @include('filament.partials.reports-wajebaty-styles')

        <div class="ci-rep-title">الإيرادات حسب العميل</div>

        <div class="ci-card ci-form-inner ci-reports-card ci-rep-no-print">
            <div class="ci-rep-filters">
                <div class="ci-rep-field">
                    <label for="rc-cust">اختيار العميل</label>
                    <select id="rc-cust" wire:model.live="customerId">
                        <option value="">الكل</option>
                        @foreach ($this->customersOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ci-rep-field">
                    <label for="rc-from">التاريخ من:</label>
                    <input id="rc-from" type="date" wire:model="dateFrom" />
                </div>
                <div class="ci-rep-field">
                    <label for="rc-to">إلى:</label>
                    <input id="rc-to" type="date" wire:model="dateTo" />
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
                <div><span>العميل :</span> {{ $this->headerCustomerLabel() }}</div>
            </div>

            <p class="ci-rep-co">{{ $this->companyDisplayName() }}</p>

            <div class="ci-rep-table-wrap">
                <table>
                    <thead>
                        <tr>
                            @if ($customerId === '' || $customerId === null)
                                <th>اسم العميل</th>
                            @endif
                            <th>التاريخ</th>
                            <th>رقم الفاتورة</th>
                            <th>نوع الخدمة</th>
                            <th class="ci-rep-num">القيمة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (! $hasSearched)
                            <tr>
                                <td
                                    colspan="{{ ($customerId === '' || $customerId === null) ? 5 : 4 }}"
                                    class="ci-rep-empty"
                                >
                                    اضغط «بحث» لعرض الإيرادات في هذه الفترة.
                                </td>
                            </tr>
                        @elseif (count($reportRows) === 0)
                            <tr>
                                <td
                                    colspan="{{ ($customerId === '' || $customerId === null) ? 5 : 4 }}"
                                    class="ci-rep-empty"
                                >
                                    لا توجد بيانات في هذه الفترة.
                                </td>
                            </tr>
                        @else
                            @foreach ($reportRows as $row)
                                <tr>
                                    @if ($customerId === '' || $customerId === null)
                                        <td class="ci-rep-text">{{ $row['customer_name'] ?? '—' }}</td>
                                    @endif
                                    <td>{{ $row['invoice_date'] }}</td>
                                    <td>{{ $row['invoice_number'] }}</td>
                                    <td class="ci-rep-text">{{ $row['service_label'] }}</td>
                                    <td class="ci-rep-num">{{ number_format($row['line_total'], 2) }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                    @if ($hasSearched)
                        <tfoot>
                            <tr>
                                <td
                                    colspan="{{ ($customerId === '' || $customerId === null) ? 4 : 3 }}"
                                    class="ci-rep-text"
                                >
                                    الإجمالي
                                </td>
                                <td class="ci-rep-num">{{ number_format($totalValue, 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
