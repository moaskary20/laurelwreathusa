<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-payroll-wajebaty ci-reports-page ci-revenue-by-service-product-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        dir="rtl"
        wire:key="revenue-by-service-product-{{ \Filament\Facades\Filament::getTenant()?->getKey() }}"
    >
        @include('filament.partials.wajebaty-theme-styles')
        @include('filament.partials.payroll-wajebaty-styles')
        @include('filament.partials.reports-wajebaty-styles')

        <div class="ci-rep-title">الإيرادات حسب نوع الخدمة والمنتج</div>

        <div class="ci-card ci-form-inner ci-reports-card ci-rep-no-print">
            <div class="ci-rep-filters-stack">
                <div class="ci-rep-filter-row">
                    <div class="ci-rep-field">
                        <label for="rsp-kind">نوع الخدمة أو المنتج</label>
                        <select id="rsp-kind" wire:model.live="kindFilter">
                            <option value="">الكل</option>
                            <option value="service">خدمة</option>
                            <option value="product">منتج</option>
                        </select>
                    </div>
                </div>
                <div class="ci-rep-filter-row">
                    <div class="ci-rep-field">
                        <label for="rsp-product">اختيار الصنف</label>
                        <select id="rsp-product" wire:model="serviceProductId">
                            <option value="">الكل</option>
                            @foreach ($this->serviceProductsOptions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="ci-rep-filter-row">
                    <div class="ci-rep-field">
                        <label for="rsp-from">التاريخ من:</label>
                        <input id="rsp-from" type="date" wire:model="dateFrom" />
                    </div>
                    <div class="ci-rep-field">
                        <label for="rsp-to">إلى:</label>
                        <input id="rsp-to" type="date" wire:model="dateTo" />
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

            <p class="ci-rep-co">{{ $this->companyDisplayName() }}</p>

            <div class="ci-rep-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>اسم العميل</th>
                            <th>رقم الفاتورة</th>
                            <th>نوع المستند</th>
                            <th>نوع الخدمة</th>
                            <th class="ci-rep-num">القيمة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (! $hasSearched)
                            <tr>
                                <td colspan="6" class="ci-rep-empty">
                                    اضغط «بحث» لعرض الإيرادات في هذه الفترة.
                                </td>
                            </tr>
                        @elseif (count($reportRows) === 0)
                            <tr>
                                <td colspan="6" class="ci-rep-empty">
                                    لا توجد بيانات في هذه الفترة.
                                </td>
                            </tr>
                        @else
                            @foreach ($reportRows as $row)
                                <tr>
                                    <td>{{ $row['invoice_date'] }}</td>
                                    <td class="ci-rep-text">{{ $row['customer_name'] }}</td>
                                    <td>{{ $row['invoice_number'] }}</td>
                                    <td>{{ $row['document_type'] }}</td>
                                    <td class="ci-rep-text">{{ $row['service_kind'] }}</td>
                                    <td class="ci-rep-num">{{ number_format($row['line_total'], 2) }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                    @if ($hasSearched)
                        <tfoot>
                            <tr>
                                <td colspan="5" class="ci-rep-text">الإجمالي</td>
                                <td class="ci-rep-num">{{ number_format($totalValue, 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
