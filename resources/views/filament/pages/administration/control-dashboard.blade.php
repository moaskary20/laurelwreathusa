<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-payroll-wajebaty ci-reports-page ci-control-dashboard -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        dir="rtl"
        wire:key="control-dashboard-{{ \Filament\Facades\Filament::getTenant()?->getKey() }}"
    >
        @include('filament.partials.wajebaty-theme-styles')
        @include('filament.partials.payroll-wajebaty-styles')
        @include('filament.partials.reports-wajebaty-styles')

        <style>
            .ci-control-dashboard .ci-dash-kpis {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));
                gap: 0.85rem;
                margin-bottom: 1rem;
            }

            .ci-control-dashboard .ci-dash-kpi {
                background: linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.03));
                border: 1px solid rgba(255, 255, 255, 0.12);
                border-radius: 0.75rem;
                padding: 1rem 1.1rem;
            }

            .ci-control-dashboard .ci-dash-kpi__label {
                font-size: 0.8rem;
                color: rgba(255, 255, 255, 0.72);
                margin-bottom: 0.35rem;
            }

            .ci-control-dashboard .ci-dash-kpi__value {
                font-size: 1.35rem;
                font-weight: 800;
                color: #fff;
                line-height: 1.2;
            }

            .ci-control-dashboard .ci-dash-kpi__meta {
                margin-top: 0.35rem;
                font-size: 0.75rem;
                color: rgba(255, 255, 255, 0.55);
            }

            .ci-control-dashboard .ci-dash-kpi--sales { border-top: 3px solid #f59e0b; }
            .ci-control-dashboard .ci-dash-kpi--purchase { border-top: 3px solid #ef4444; }
            .ci-control-dashboard .ci-dash-kpi--revenue { border-top: 3px solid #22c55e; }
            .ci-control-dashboard .ci-dash-kpi--expense { border-top: 3px solid #fb7185; }
            .ci-control-dashboard .ci-dash-kpi--profit { border-top: 3px solid #38bdf8; }

            .ci-control-dashboard .ci-dash-charts {
                display: grid;
                grid-template-columns: repeat(12, minmax(0, 1fr));
                gap: 1rem;
                margin-bottom: 1rem;
            }

            .ci-control-dashboard .ci-dash-chart-card {
                grid-column: span 12;
                background: var(--ci-card);
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 0.75rem;
                padding: 1rem 1.1rem;
            }

            @media (min-width: 900px) {
                .ci-control-dashboard .ci-dash-chart-card--wide { grid-column: span 8; }
                .ci-control-dashboard .ci-dash-chart-card--side { grid-column: span 4; }
                .ci-control-dashboard .ci-dash-chart-card--half { grid-column: span 6; }
            }

            .ci-control-dashboard .ci-dash-chart-card h3 {
                margin: 0 0 0.85rem;
                font-size: 0.95rem;
                font-weight: 700;
                color: rgba(255, 255, 255, 0.94);
            }

            .ci-control-dashboard .ci-dash-chart-wrap {
                position: relative;
                height: 18rem;
            }

            .ci-control-dashboard .ci-dash-chart-wrap--sm {
                height: 14rem;
            }

            .ci-control-dashboard .ci-dash-section-title {
                margin: 0 0 0.75rem;
                font-size: 1rem;
                font-weight: 700;
                color: rgba(255, 255, 255, 0.95);
            }

            .ci-control-dashboard .ci-rep-table-wrap table {
                width: 100%;
            }

            .ci-control-dashboard .ci-rep-empty {
                text-align: center;
                color: rgba(255, 255, 255, 0.65);
                padding: 1.25rem;
            }
        </style>

        <div class="ci-banner">
            <div class="ci-banner__main">
                <div class="ci-banner__icon" aria-hidden="true">
                    <x-filament::icon icon="heroicon-o-presentation-chart-line" class="h-8 w-8" />
                </div>
                <div class="min-w-0">
                    <div class="ci-banner__title">لوحة التحكم</div>
                    <div class="ci-banner__sub">
                        {{ $this->companyDisplayName() }} — {{ $this->periodLabel() }}
                    </div>
                </div>
            </div>
        </div>

        <div class="ci-card ci-form-inner ci-reports-card ci-rep-no-print">
            <div class="ci-rep-filters">
                <div class="ci-rep-field">
                    <label for="dash-date-from">الفترة من</label>
                    <input id="dash-date-from" type="date" wire:model="dateFrom" />
                </div>
                <div class="ci-rep-field">
                    <label for="dash-date-to">إلى</label>
                    <input id="dash-date-to" type="date" wire:model="dateTo" />
                </div>
                <x-filament::button
                    type="button"
                    wire:click="loadDashboard"
                    icon="heroicon-o-arrow-path"
                    color="warning"
                >
                    تحديث
                </x-filament::button>
            </div>
        </div>

        @if ($hasLoaded)
            @php($summary = $this->summary())

            <div class="ci-dash-kpis">
                <div class="ci-dash-kpi ci-dash-kpi--sales">
                    <div class="ci-dash-kpi__label">فواتير مبيعات مستحقة السداد</div>
                    <div class="ci-dash-kpi__value">{{ \App\Support\ControlDashboardMetrics::formatMoney((float) ($summary['sales_due_total'] ?? 0)) }}</div>
                    <div class="ci-dash-kpi__meta">{{ (int) ($summary['sales_due_count'] ?? 0) }} فاتورة</div>
                </div>
                <div class="ci-dash-kpi ci-dash-kpi--purchase">
                    <div class="ci-dash-kpi__label">فواتير مشتريات مستحقة السداد</div>
                    <div class="ci-dash-kpi__value">{{ \App\Support\ControlDashboardMetrics::formatMoney((float) ($summary['purchase_due_total'] ?? 0)) }}</div>
                    <div class="ci-dash-kpi__meta">{{ (int) ($summary['purchase_due_count'] ?? 0) }} فاتورة</div>
                </div>
                <div class="ci-dash-kpi ci-dash-kpi--revenue">
                    <div class="ci-dash-kpi__label">قيمة الإيرادات</div>
                    <div class="ci-dash-kpi__value">{{ \App\Support\ControlDashboardMetrics::formatMoney((float) ($summary['revenue'] ?? 0)) }}</div>
                    <div class="ci-dash-kpi__meta">ضمن الفترة المحددة</div>
                </div>
                <div class="ci-dash-kpi ci-dash-kpi--expense">
                    <div class="ci-dash-kpi__label">قيمة المصاريف</div>
                    <div class="ci-dash-kpi__value">{{ \App\Support\ControlDashboardMetrics::formatMoney((float) ($summary['expenses'] ?? 0)) }}</div>
                    <div class="ci-dash-kpi__meta">ضمن الفترة المحددة</div>
                </div>
                <div class="ci-dash-kpi ci-dash-kpi--profit">
                    <div class="ci-dash-kpi__label">صافي الربح</div>
                    <div class="ci-dash-kpi__value">{{ \App\Support\ControlDashboardMetrics::formatMoney((float) ($summary['net_profit'] ?? 0)) }}</div>
                    <div class="ci-dash-kpi__meta">الإيرادات − المصاريف</div>
                </div>
            </div>

            <div class="ci-dash-charts">
                <div class="ci-dash-chart-card ci-dash-chart-card--wide">
                    <h3>اتجاه الإيرادات والمصاريف وصافي الربح (آخر 12 شهراً)</h3>
                    <div class="ci-dash-chart-wrap" wire:ignore>
                        <canvas id="chart-monthly-trend"></canvas>
                    </div>
                </div>
                <div class="ci-dash-chart-card ci-dash-chart-card--side">
                    <h3>مقارنة المستحقات</h3>
                    <div class="ci-dash-chart-wrap ci-dash-chart-wrap--sm" wire:ignore>
                        <canvas id="chart-due-comparison"></canvas>
                    </div>
                </div>
                <div class="ci-dash-chart-card ci-dash-chart-card--half">
                    <h3>توزيع الإيرادات حسب العميل</h3>
                    <div class="ci-dash-chart-wrap ci-dash-chart-wrap--sm" wire:ignore>
                        <canvas id="chart-revenue-customer"></canvas>
                    </div>
                </div>
                <div class="ci-dash-chart-card ci-dash-chart-card--half">
                    <h3>توزيع المصاريف حسب المورد</h3>
                    <div class="ci-dash-chart-wrap ci-dash-chart-wrap--sm" wire:ignore>
                        <canvas id="chart-expenses-supplier"></canvas>
                    </div>
                </div>
                <div class="ci-dash-chart-card ci-dash-chart-card--half">
                    <h3>الإيرادات حسب الحساب</h3>
                    <div class="ci-dash-chart-wrap ci-dash-chart-wrap--sm" wire:ignore>
                        <canvas id="chart-revenue-account"></canvas>
                    </div>
                </div>
                <div class="ci-dash-chart-card ci-dash-chart-card--half">
                    <h3>المصاريف حسب الحساب</h3>
                    <div class="ci-dash-chart-wrap ci-dash-chart-wrap--sm" wire:ignore>
                        <canvas id="chart-expenses-account"></canvas>
                    </div>
                </div>
                <div class="ci-dash-chart-card ci-dash-chart-card--wide">
                    <h3>ملخص الأداء المالي للفترة</h3>
                    <div class="ci-dash-chart-wrap ci-dash-chart-wrap--sm" wire:ignore>
                        <canvas id="chart-kpi-bars"></canvas>
                    </div>
                </div>
            </div>

            <div class="ci-card ci-form-inner ci-reports-card">
                <h3 class="ci-dash-section-title">فواتير المبيعات المستحقة السداد</h3>
                <div class="ci-rep-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>رقم الفاتورة</th>
                                <th>العميل</th>
                                <th>تاريخ الفاتورة</th>
                                <th>تاريخ الاستحقاق</th>
                                <th class="ci-rep-col-bal">الإجمالي</th>
                                <th class="ci-rep-col-bal">المدفوع</th>
                                <th class="ci-rep-col-bal">المتبقي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($metrics['sales_due_invoices'] ?? [] as $row)
                                <tr>
                                    <td>{{ $row['number'] }}</td>
                                    <td>{{ $row['party_name'] }}</td>
                                    <td>{{ \App\Support\ControlDashboardMetrics::formatDate($row['invoice_date']) }}</td>
                                    <td>{{ \App\Support\ControlDashboardMetrics::formatDate($row['due_date']) }}</td>
                                    <td class="ci-rep-col-bal">{{ \App\Support\ControlDashboardMetrics::formatMoney($row['grand_total']) }}</td>
                                    <td class="ci-rep-col-bal">{{ \App\Support\ControlDashboardMetrics::formatMoney($row['paid_amount']) }}</td>
                                    <td class="ci-rep-col-bal">{{ \App\Support\ControlDashboardMetrics::formatMoney($row['outstanding']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="ci-rep-empty">لا توجد فواتير مبيعات مستحقة السداد.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="ci-card ci-form-inner ci-reports-card">
                <h3 class="ci-dash-section-title">فواتير المشتريات المستحقة السداد</h3>
                <div class="ci-rep-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>رقم الفاتورة</th>
                                <th>المورد</th>
                                <th>تاريخ الفاتورة</th>
                                <th>تاريخ الاستحقاق</th>
                                <th class="ci-rep-col-bal">الإجمالي</th>
                                <th class="ci-rep-col-bal">المدفوع</th>
                                <th class="ci-rep-col-bal">المتبقي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($metrics['purchase_due_invoices'] ?? [] as $row)
                                <tr>
                                    <td>{{ $row['number'] }}</td>
                                    <td>{{ $row['party_name'] }}</td>
                                    <td>{{ \App\Support\ControlDashboardMetrics::formatDate($row['invoice_date']) }}</td>
                                    <td>{{ \App\Support\ControlDashboardMetrics::formatDate($row['due_date']) }}</td>
                                    <td class="ci-rep-col-bal">{{ \App\Support\ControlDashboardMetrics::formatMoney($row['grand_total']) }}</td>
                                    <td class="ci-rep-col-bal">{{ \App\Support\ControlDashboardMetrics::formatMoney($row['paid_amount']) }}</td>
                                    <td class="ci-rep-col-bal">{{ \App\Support\ControlDashboardMetrics::formatMoney($row['outstanding']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="ci-rep-empty">لا توجد فواتير مشتريات مستحقة السداد.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="ci-card ci-form-inner ci-reports-card">
                <h3 class="ci-dash-section-title">تقرير الإيرادات حسب العميل</h3>
                <div class="ci-rep-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>العميل</th>
                                <th class="ci-rep-col-bal">القيمة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($metrics['revenue_by_customer'] ?? [] as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="ci-rep-col-bal">{{ \App\Support\ControlDashboardMetrics::formatMoney($row['total']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="ci-rep-empty">لا توجد إيرادات في هذه الفترة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="ci-card ci-form-inner ci-reports-card">
                <h3 class="ci-dash-section-title">تقرير المصاريف حسب المورد</h3>
                <div class="ci-rep-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>المورد</th>
                                <th class="ci-rep-col-bal">القيمة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($metrics['expenses_by_supplier'] ?? [] as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="ci-rep-col-bal">{{ \App\Support\ControlDashboardMetrics::formatMoney($row['total']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="ci-rep-empty">لا توجد مصاريف في هذه الفترة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="ci-card ci-form-inner ci-reports-card">
                <h3 class="ci-dash-section-title">قائمة الدخل المختصرة</h3>
                <div class="ci-rep-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>البند</th>
                                <th class="ci-rep-col-bal">القيمة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="ci-rep-section"><td colspan="2">الإيرادات حسب الحساب</td></tr>
                            @forelse ($metrics['revenue_by_account'] ?? [] as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="ci-rep-col-bal">{{ \App\Support\ControlDashboardMetrics::formatMoney($row['total']) }}</td>
                                </tr>
                            @empty
                                <tr class="ci-rep-muted"><td colspan="2">لا توجد إيرادات</td></tr>
                            @endforelse
                            <tr class="ci-rep-subtotal">
                                <td>إجمالي الإيرادات</td>
                                <td class="ci-rep-col-bal">{{ \App\Support\ControlDashboardMetrics::formatMoney((float) ($summary['revenue'] ?? 0)) }}</td>
                            </tr>
                            <tr class="ci-rep-section"><td colspan="2">المصاريف حسب الحساب</td></tr>
                            @forelse ($metrics['expenses_by_account'] ?? [] as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="ci-rep-col-bal">{{ \App\Support\ControlDashboardMetrics::formatMoney($row['total']) }}</td>
                                </tr>
                            @empty
                                <tr class="ci-rep-muted"><td colspan="2">لا توجد مصاريف</td></tr>
                            @endforelse
                            <tr class="ci-rep-subtotal">
                                <td>إجمالي المصاريف</td>
                                <td class="ci-rep-col-bal">{{ \App\Support\ControlDashboardMetrics::formatMoney((float) ($summary['expenses'] ?? 0)) }}</td>
                            </tr>
                            <tr class="ci-rep-net">
                                <td>صافي الربح</td>
                                <td class="ci-rep-col-bal">{{ \App\Support\ControlDashboardMetrics::formatMoney((float) ($summary['net_profit'] ?? 0)) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="ci-card ci-form-inner ci-reports-card">
                <p class="ci-rep-empty">اضغط «تحديث» لعرض لوحة التحكم.</p>
            </div>
        @endif
    </div>

    @if ($hasLoaded)
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        @script
        <script>
            const chartInstances = {};

            const chartColors = {
                revenue: 'rgba(34, 197, 94, 0.85)',
                expenses: 'rgba(251, 113, 133, 0.85)',
                profit: 'rgba(56, 189, 248, 0.9)',
                salesDue: 'rgba(245, 158, 11, 0.9)',
                purchaseDue: 'rgba(239, 68, 68, 0.9)',
                palette: [
                    '#22c55e', '#38bdf8', '#f59e0b', '#a78bfa', '#fb7185',
                    '#2dd4bf', '#f97316', '#818cf8', '#4ade80', '#facc15',
                ],
            };

            const baseChartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: 'rgba(255,255,255,0.88)' },
                    },
                },
                scales: {
                    x: {
                        ticks: { color: 'rgba(255,255,255,0.75)' },
                        grid: { color: 'rgba(255,255,255,0.08)' },
                    },
                    y: {
                        ticks: { color: 'rgba(255,255,255,0.75)' },
                        grid: { color: 'rgba(255,255,255,0.08)' },
                    },
                },
            };

            function destroyChart(id) {
                if (chartInstances[id]) {
                    chartInstances[id].destroy();
                    delete chartInstances[id];
                }
            }

            function renderCharts(charts) {
                if (typeof Chart === 'undefined' || !charts) {
                    return;
                }

                destroyChart('monthly');
                destroyChart('due');
                destroyChart('revCust');
                destroyChart('expSup');
                destroyChart('revAcc');
                destroyChart('expAcc');
                destroyChart('kpi');

                const trend = charts.monthly_trend ?? { labels: [], revenue: [], expenses: [], profit: [] };
                chartInstances.monthly = new Chart(document.getElementById('chart-monthly-trend'), {
                    type: 'line',
                    data: {
                        labels: trend.labels,
                        datasets: [
                            { label: 'الإيرادات', data: trend.revenue, borderColor: chartColors.revenue, backgroundColor: 'rgba(34,197,94,0.15)', tension: 0.3, fill: true },
                            { label: 'المصاريف', data: trend.expenses, borderColor: chartColors.expenses, backgroundColor: 'rgba(251,113,133,0.12)', tension: 0.3, fill: true },
                            { label: 'صافي الربح', data: trend.profit, borderColor: chartColors.profit, backgroundColor: 'rgba(56,189,248,0.12)', tension: 0.3, fill: false },
                        ],
                    },
                    options: baseChartOptions,
                });

                const due = charts.due_comparison ?? { labels: [], values: [] };
                chartInstances.due = new Chart(document.getElementById('chart-due-comparison'), {
                    type: 'doughnut',
                    data: {
                        labels: due.labels,
                        datasets: [{ data: due.values, backgroundColor: [chartColors.salesDue, chartColors.purchaseDue] }],
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: 'rgba(255,255,255,0.88)' } } } },
                });

                const revCust = charts.revenue_by_customer ?? { labels: [], values: [] };
                chartInstances.revCust = new Chart(document.getElementById('chart-revenue-customer'), {
                    type: 'bar',
                    data: {
                        labels: revCust.labels,
                        datasets: [{ label: 'الإيرادات', data: revCust.values, backgroundColor: chartColors.palette }],
                    },
                    options: { ...baseChartOptions, indexAxis: 'y' },
                });

                const expSup = charts.expenses_by_supplier ?? { labels: [], values: [] };
                chartInstances.expSup = new Chart(document.getElementById('chart-expenses-supplier'), {
                    type: 'bar',
                    data: {
                        labels: expSup.labels,
                        datasets: [{ label: 'المصاريف', data: expSup.values, backgroundColor: chartColors.palette }],
                    },
                    options: { ...baseChartOptions, indexAxis: 'y' },
                });

                const revAcc = charts.revenue_by_account ?? { labels: [], values: [] };
                chartInstances.revAcc = new Chart(document.getElementById('chart-revenue-account'), {
                    type: 'pie',
                    data: {
                        labels: revAcc.labels,
                        datasets: [{ data: revAcc.values, backgroundColor: chartColors.palette }],
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: 'rgba(255,255,255,0.88)' } } } },
                });

                const expAcc = charts.expenses_by_account ?? { labels: [], values: [] };
                chartInstances.expAcc = new Chart(document.getElementById('chart-expenses-account'), {
                    type: 'pie',
                    data: {
                        labels: expAcc.labels,
                        datasets: [{ data: expAcc.values, backgroundColor: chartColors.palette }],
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: 'rgba(255,255,255,0.88)' } } } },
                });

                const kpi = charts.kpi ?? { labels: [], values: [] };
                chartInstances.kpi = new Chart(document.getElementById('chart-kpi-bars'), {
                    type: 'bar',
                    data: {
                        labels: kpi.labels,
                        datasets: [{
                            label: 'القيمة',
                            data: kpi.values,
                            backgroundColor: [chartColors.salesDue, chartColors.purchaseDue, chartColors.revenue, chartColors.expenses, chartColors.profit],
                        }],
                    },
                    options: baseChartOptions,
                });
            }

            renderCharts(@js($metrics['charts'] ?? []));

            $wire.on('control-dashboard-charts-updated', (event) => {
                renderCharts(event.charts ?? event[0]?.charts ?? {});
            });
        </script>
        @endscript
    @endif
</x-filament-panels::page>
