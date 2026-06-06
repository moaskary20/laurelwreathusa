<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-style-editor -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        wire:key="edit-style"
    >
        @include('filament.partials.wajebaty-theme-styles')

        <style>
            .ci-style-editor .ci-style-preview {
                display: grid;
                gap: 0.75rem;
                grid-template-columns: repeat(auto-fill, minmax(8.5rem, 1fr));
                margin-top: 1.25rem;
            }

            .ci-style-editor .ci-style-swatch {
                border-radius: 0.75rem;
                padding: 1rem 0.75rem;
                text-align: center;
                font-size: 0.78rem;
                font-weight: 700;
                color: var(--admin-text);
                border: 1px solid var(--admin-border);
                min-height: 4.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .ci-style-editor .ci-style-swatch--on-dark {
                color: #ffffff;
            }
        </style>

        <div class="ci-banner">
            <div class="ci-banner__main">
                <div class="ci-banner__icon">
                    <x-heroicon-o-swatch class="h-8 w-8" />
                </div>
                <div>
                    <div class="ci-banner__title">تعديل ال style</div>
                    <div class="ci-banner__sub">تخصيص ألوان لوحة التحكم بالكامل</div>
                </div>
            </div>
        </div>

        <div class="ci-card">
            <div class="ci-card__head">
                <x-heroicon-o-paint-brush class="h-5 w-5" />
                <h2>ألوان لوحة التحكم</h2>
            </div>

            <div class="ci-form-inner">
                {{ $this->form }}

                <div class="ci-style-preview">
                    <div class="ci-style-swatch" style="background: var(--admin-primary); color: #fff;">أساسي</div>
                    <div class="ci-style-swatch" style="background: var(--admin-secondary); color: #fff;">ثانوي</div>
                    <div class="ci-style-swatch" style="background: var(--admin-text); color: #fff;">خط</div>
                    <div class="ci-style-swatch" style="background: var(--admin-active); color: #fff;">نشط</div>
                    <div class="ci-style-swatch" style="background: var(--admin-background);">خلفية</div>
                    <div class="ci-style-swatch" style="background: var(--admin-sidebar-background);">قائمة</div>
                    <div class="ci-style-swatch" style="background: var(--admin-logo-header-background);">لوجو</div>
                    <div class="ci-style-swatch" style="background: var(--admin-input-background);">إدخال</div>
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
