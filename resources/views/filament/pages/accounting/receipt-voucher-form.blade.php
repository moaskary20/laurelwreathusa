@php
    /** @var \App\Models\Company|null $tenant */
    $tenant = \Filament\Facades\Filament::getTenant();
    $customerPreview = isset($this->data['customer_id'])
        ? \App\Models\Customer::query()->find($this->data['customer_id'])
        : null;
@endphp

<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-receipt-voucher-form-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        dir="rtl"
    >
        @include('filament.partials.wajebaty-theme-styles')

        <style>
            .ci-receipt-voucher-form-page .ci-rv-title {
                text-align: center;
                font-size: 1.25rem;
                font-weight: 700;
                color: rgba(255, 255, 255, 0.95);
                margin-bottom: 1rem;
            }

            .ci-receipt-voucher-form-page .ci-rv-top-grid {
                display: grid;
                grid-template-columns: 1fr 140px 1fr;
                gap: 1rem;
                margin-bottom: 1.25rem;
                align-items: start;
            }

            @media (max-width: 1024px) {
                .ci-receipt-voucher-form-page .ci-rv-top-grid {
                    grid-template-columns: 1fr;
                }
            }

            .ci-receipt-voucher-form-page .ci-rv-balance {
                border-radius: 0.5rem;
                border: 1px solid var(--ci-line);
                background: rgba(26, 32, 41, 0.95);
                padding: 0.75rem 1rem;
                text-align: center;
            }

            .ci-receipt-voucher-form-page .ci-rv-balance .k {
                font-size: 0.75rem;
                color: rgba(255, 255, 255, 0.65);
            }

            .ci-receipt-voucher-form-page .ci-rv-balance .v {
                font-size: 1.1rem;
                font-weight: 700;
                color: #fbbf24;
            }

            .ci-receipt-voucher-form-page .ci-rv-meta {
                border-radius: 0.5rem;
                border: 1px solid var(--ci-line);
                background: rgba(15, 18, 24, 0.65);
                padding: 1rem;
                font-size: 0.9rem;
                color: rgba(255, 255, 255, 0.88);
            }

            @media print {
                .fi-sidebar,
                .fi-topbar,
                .fi-header {
                    display: none !important;
                }
            }
        </style>

        <div class="ci-rv-title">سند قبض</div>

        @if ($tenant instanceof \App\Models\Company)
            <div class="ci-rv-top-grid">
                <div class="ci-rv-meta space-y-2">
                    <div>
                        <span class="text-white/55">رقم سند قبض:</span>
                        <span class="font-semibold">{{ $this->data['receipt_number'] ?? '—' }}</span>
                    </div>
                    @if ($customerPreview)
                        <div>
                            <span class="text-white/55">اسم العميل:</span>
                            <span class="font-semibold">{{ $customerPreview->name_ar }}</span>
                        </div>
                    @endif
                </div>
                <div class="ci-rv-balance">
                    <div class="k">الرصيد</div>
                    <div class="v">
                        {{ $customerPreview ? number_format((float) $customerPreview->balance, 2) : '0' }}
                    </div>
                </div>
                <div></div>
            </div>
        @endif

        <x-filament-panels::form id="receipt-voucher-form" wire:submit="save">
            {{ $this->form }}

            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        </x-filament-panels::form>
    </div>
</x-filament-panels::page>
