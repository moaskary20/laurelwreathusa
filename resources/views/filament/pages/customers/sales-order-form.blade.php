@php
    /** @var \App\Models\Company|null $tenant */
    $tenant = \Filament\Facades\Filament::getTenant();
    $customerPreview = isset($this->data['customer_id'])
        ? \App\Models\Customer::query()->find($this->data['customer_id'])
        : null;
@endphp

<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-sales-order-form-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        dir="rtl"
    >
        @include('filament.partials.wajebaty-theme-styles')

        <style>
            .ci-sales-order-form-page .ci-so-title {
                text-align: center;
                font-size: 1.25rem;
                font-weight: 700;
                color: rgba(255, 255, 255, 0.95);
                margin-bottom: 1rem;
            }

            .ci-sales-order-form-page .ci-so-top-grid {
                display: grid;
                grid-template-columns: 1fr 140px 1fr;
                gap: 1rem;
                margin-bottom: 1.25rem;
                align-items: start;
            }

            @media (max-width: 1024px) {
                .ci-sales-order-form-page .ci-so-top-grid {
                    grid-template-columns: 1fr;
                }
            }

            .ci-sales-order-form-page .ci-so-balance {
                border-radius: 0.5rem;
                border: 1px solid var(--ci-line);
                background: rgba(26, 32, 41, 0.95);
                padding: 0.75rem 1rem;
                text-align: center;
            }

            .ci-sales-order-form-page .ci-so-balance .k {
                font-size: 0.75rem;
                color: rgba(255, 255, 255, 0.65);
            }

            .ci-sales-order-form-page .ci-so-balance .v {
                font-size: 1.1rem;
                font-weight: 700;
                color: #fbbf24;
            }

            .ci-sales-order-form-page .ci-so-company-box {
                border-radius: 0.5rem;
                border: 1px solid var(--ci-line);
                background: rgba(15, 18, 24, 0.65);
                padding: 1rem;
                font-size: 0.85rem;
                line-height: 1.6;
                color: rgba(255, 255, 255, 0.88);
            }

            .ci-sales-order-form-page .ci-so-company-box dt {
                color: rgba(255, 255, 255, 0.55);
                display: inline;
            }

            .ci-sales-order-form-page .ci-so-company-box dd {
                display: inline;
                margin-inline-start: 0.35rem;
            }

            .ci-sales-order-form-page .ci-so-customer-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 0.8rem;
                margin-bottom: 1rem;
                border-radius: 0.5rem;
                overflow: hidden;
                border: 1px solid var(--ci-line);
            }

            .ci-sales-order-form-page .ci-so-customer-table th,
            .ci-sales-order-form-page .ci-so-customer-table td {
                border: 1px solid var(--ci-line);
                padding: 0.5rem 0.65rem;
                text-align: center;
            }

            .ci-sales-order-form-page .ci-so-customer-table th {
                background: rgba(0, 139, 163, 0.2);
                color: #e8f7fa;
            }

            @media print {
                .fi-sidebar,
                .fi-topbar,
                .fi-header {
                    display: none !important;
                }
            }
        </style>

        <div class="ci-so-title">امر البيع</div>

        @if ($tenant instanceof \App\Models\Company)
            <div class="ci-so-top-grid">
                <div class="ci-so-company-box">
                    <dl class="space-y-1">
                        <div><dt>اسم الشركة القانوني</dt><dd>{{ $tenant->legal_name ?: '—' }}</dd></div>
                        <div><dt>العنوان</dt><dd>{{ $tenant->address ?: '—' }}</dd></div>
                        <div><dt>رقم الهاتف</dt><dd>{{ $tenant->phone ?: '—' }}</dd></div>
                        <div><dt>الايميل</dt><dd>{{ $tenant->email ?: '—' }}</dd></div>
                        <div><dt>رقم ضريبة المبيعات</dt><dd>{{ $tenant->sales_tax_number ?: '—' }}</dd></div>
                        <div><dt>رقم الشركة الوطني</dt><dd>{{ $tenant->national_number ?: '—' }}</dd></div>
                    </dl>
                </div>
                <div class="ci-so-balance">
                    <div class="k">الرصيد</div>
                    <div class="v">
                        {{ $customerPreview ? number_format((float) $customerPreview->balance, 2) : '—' }}
                    </div>
                </div>
                <div></div>
            </div>
        @endif

        @if ($customerPreview)
            <table class="ci-so-customer-table">
                <thead>
                    <tr>
                        <th>كود العميل</th>
                        <th>اسم العميل</th>
                        <th>العنوان</th>
                        <th>رقم الهاتف</th>
                        <th>رقم ضريبة المبيعات</th>
                        <th>الرقم الوطني</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ str_pad((string) $customerPreview->getKey(), 5, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $customerPreview->name_ar }}</td>
                        <td>{{ $customerPreview->address_ar ?: '—' }}</td>
                        <td>{{ $customerPreview->phone ?: '—' }}</td>
                        <td>{{ $customerPreview->sales_tax_number ?: '—' }}</td>
                        <td>—</td>
                    </tr>
                </tbody>
            </table>
        @endif

        <x-filament-panels::form id="sales-order-form" wire:submit="save">
            {{ $this->form }}

            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        </x-filament-panels::form>
    </div>
</x-filament-panels::page>
