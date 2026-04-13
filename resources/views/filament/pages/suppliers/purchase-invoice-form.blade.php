@php
    /** @var \App\Models\Company|null $tenant */
    $tenant = \Filament\Facades\Filament::getTenant();
    $supplierPreview = isset($this->data['supplier_id'])
        ? \App\Models\Supplier::query()->find($this->data['supplier_id'])
        : null;
@endphp

<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-purchase-invoice-form-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        dir="rtl"
    >
        @include('filament.partials.wajebaty-theme-styles')

        <style>
            .ci-purchase-invoice-form-page .ci-so-title {
                text-align: center;
                font-size: 1.25rem;
                font-weight: 700;
                color: rgba(255, 255, 255, 0.95);
                margin-bottom: 1rem;
            }

            .ci-purchase-invoice-form-page .ci-so-top-grid {
                display: grid;
                grid-template-columns: 1fr 140px 1fr;
                gap: 1rem;
                margin-bottom: 1.25rem;
                align-items: start;
            }

            @media (max-width: 1024px) {
                .ci-purchase-invoice-form-page .ci-so-top-grid {
                    grid-template-columns: 1fr;
                }
            }

            .ci-purchase-invoice-form-page .ci-so-balance {
                border-radius: 0.5rem;
                border: 1px solid var(--ci-line);
                background: rgba(26, 32, 41, 0.95);
                padding: 0.75rem 1rem;
                text-align: center;
            }

            .ci-purchase-invoice-form-page .ci-so-balance .k {
                font-size: 0.75rem;
                color: rgba(255, 255, 255, 0.65);
            }

            .ci-purchase-invoice-form-page .ci-so-balance .v {
                font-size: 1.1rem;
                font-weight: 700;
                color: #fbbf24;
            }

            .ci-purchase-invoice-form-page .ci-so-supplier-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 0.8rem;
                margin-bottom: 1rem;
                border-radius: 0.5rem;
                overflow: hidden;
                border: 1px solid var(--ci-line);
            }

            .ci-purchase-invoice-form-page .ci-so-supplier-table th,
            .ci-purchase-invoice-form-page .ci-so-supplier-table td {
                border: 1px solid var(--ci-line);
                padding: 0.5rem 0.65rem;
                text-align: center;
            }

            .ci-purchase-invoice-form-page .ci-so-supplier-table th {
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

        <div class="ci-so-title">فاتورة المشتريات</div>

        @if ($tenant instanceof \App\Models\Company)
            <div class="ci-so-top-grid">
                <div></div>
                <div class="ci-so-balance">
                    <div class="k">الرصيد</div>
                    <div class="v">
                        {{ $supplierPreview ? number_format((float) $supplierPreview->balance, 2) : '—' }}
                    </div>
                </div>
                <div></div>
            </div>
        @endif

        @if ($supplierPreview)
            <table class="ci-so-supplier-table">
                <thead>
                    <tr>
                        <th>كود المورد</th>
                        <th>العنوان</th>
                        <th>رقم الهاتف</th>
                        <th>رقم ضريبة المبيعات</th>
                        <th>الرقم الوطني</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ str_pad((string) $supplierPreview->getKey(), 5, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $supplierPreview->address_ar ?: '—' }}</td>
                        <td>{{ $supplierPreview->phone ?: '—' }}</td>
                        <td>{{ $supplierPreview->sales_tax_number ?: '—' }}</td>
                        <td>—</td>
                    </tr>
                </tbody>
            </table>
        @endif

        <x-filament-panels::form id="purchase-invoice-form" wire:submit="save">
            {{ $this->form }}

            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        </x-filament-panels::form>
    </div>
</x-filament-panels::page>
