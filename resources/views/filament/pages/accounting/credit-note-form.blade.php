@php
    $tenant = \Filament\Facades\Filament::getTenant();
@endphp

<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-credit-note-form-page -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        dir="rtl"
    >
        @include('filament.partials.wajebaty-theme-styles')

        <style>
            .ci-credit-note-form-page .ci-cn-title {
                text-align: center;
                font-size: 1.25rem;
                font-weight: 700;
                color: rgba(255, 255, 255, 0.95);
                margin-bottom: 1.25rem;
            }

            .ci-credit-note-form-page .ci-cn-meta {
                border-radius: 0.5rem;
                border: 1px solid var(--ci-line);
                background: rgba(15, 18, 24, 0.65);
                padding: 1rem;
                font-size: 0.9rem;
                color: rgba(255, 255, 255, 0.88);
                margin-bottom: 1.25rem;
            }

            @media print {
                .fi-sidebar,
                .fi-topbar,
                .fi-header {
                    display: none !important;
                }
            }
        </style>

        <div class="ci-cn-title">اشعار دائن</div>

        @if ($tenant instanceof \App\Models\Company)
            <div class="ci-cn-meta">
                <span class="text-white/55">رقم الاشعار الدائن:</span>
                <span class="font-semibold">{{ $this->data['document_number'] ?? '—' }}</span>
            </div>
        @endif

        <x-filament-panels::form id="credit-note-form" wire:submit="save">
            {{ $this->form }}

            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        </x-filament-panels::form>
    </div>
</x-filament-panels::page>
