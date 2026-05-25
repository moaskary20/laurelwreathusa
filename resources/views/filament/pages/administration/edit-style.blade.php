<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-style-editor -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        wire:key="edit-style"
    >
        @include('filament.partials.wajebaty-theme-styles')

        <style>
            .ci-style-editor .ci-style-preview {
                display: grid;
                gap: 1rem;
                grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr));
                margin-top: 1rem;
            }

            .ci-style-editor .ci-style-swatch {
                border-radius: 0.75rem;
                padding: 1.25rem 1rem;
                text-align: center;
                font-weight: 700;
                color: #fff;
                border: 1px solid var(--ci-line);
            }

            .ci-style-editor .ci-style-swatch--primary {
                background: var(--admin-primary);
            }

            .ci-style-editor .ci-style-swatch--secondary {
                background: var(--admin-secondary);
            }

            .ci-style-editor .ci-form-inner .fi-fo-field-wrp-label {
                color: var(--ci-teal-bright) !important;
            }
        </style>

        <div class="ci-banner">
            <div class="ci-banner__main">
                <div class="ci-banner__icon">
                    <x-heroicon-o-swatch class="h-8 w-8" />
                </div>
                <div>
                    <div class="ci-banner__title">تعديل ال style</div>
                    <div class="ci-banner__sub">تخصيص ألوان لوحة التحكم للنظام المحاسبي</div>
                </div>
            </div>
        </div>

        <div class="ci-card">
            <div class="ci-card__head">
                <x-heroicon-o-paint-brush class="h-5 w-5" />
                <h2>الألوان</h2>
            </div>

            <div class="ci-form-inner">
                {{ $this->form }}

                <div class="ci-style-preview">
                    <div class="ci-style-swatch ci-style-swatch--primary">أساسي</div>
                    <div class="ci-style-swatch ci-style-swatch--secondary">ثانوي</div>
                </div>
            </div>

            <div class="ci-actions">
                <button type="button" class="ci-btn-cancel" wire:click="resetColors">
                    إعادة الافتراضي
                </button>
                <button type="button" class="ci-btn-save" wire:click="save">
                    <x-heroicon-o-check class="h-4 w-4" />
                    حفظ
                </button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
