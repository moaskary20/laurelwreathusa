<x-filament-panels::page>
    <div
        class="ci-wajebaty ci-user-permissions -mx-4 -mt-8 px-4 pb-10 pt-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
        wire:key="user-permissions-{{ \Filament\Facades\Filament::getTenant()?->getKey() }}"
    >
        @include('filament.partials.wajebaty-theme-styles')

        <style>
            .ci-user-permissions .ci-card__head-perm h2 {
                font-size: 1.15rem;
                font-weight: 700;
                color: #fff;
            }

            .ci-user-permissions .ci-form-inner .fi-section-header {
                color: #fff !important;
            }

            .ci-user-permissions .ci-form-inner .fi-section-content {
                border-color: rgba(255, 255, 255, 0.12) !important;
            }

            .ci-user-permissions .ci-form-inner .fi-fo-checkbox-list {
                gap: 0.35rem 1rem;
            }

            .ci-user-permissions .ci-form-inner .fi-fo-field-wrp-label {
                color: var(--ci-teal-bright) !important;
            }

            .ci-user-permissions .ci-form-inner .fi-fo-checkbox-list-option-label {
                color: rgba(255, 255, 255, 0.9) !important;
            }

            .ci-user-permissions .ci-actions {
                display: flex;
                flex-wrap: wrap;
                justify-content: flex-end;
                gap: 0.65rem;
                margin-top: 1.25rem;
                padding-top: 1rem;
                border-top: 1px solid var(--ci-line);
            }

            .ci-user-permissions .ci-btn-save {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.55rem 1.15rem;
                border-radius: 0.55rem;
                font-weight: 700;
                font-size: 0.9rem;
                background: var(--ci-teal-bright);
                color: #0b0e11;
                border: none;
                cursor: pointer;
            }

            .ci-user-permissions .ci-btn-cancel {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.55rem 1.15rem;
                border-radius: 0.55rem;
                font-weight: 700;
                font-size: 0.9rem;
                background: rgba(0, 188, 212, 0.18);
                color: #e0f7fa;
                border: 1px solid rgba(0, 188, 212, 0.45);
                cursor: pointer;
            }
        </style>

        <div class="ci-banner">
            <div class="ci-banner__main">
                <div class="ci-banner__icon" aria-hidden="true">
                    <x-filament::icon icon="heroicon-o-shield-check" class="h-8 w-8" />
                </div>
                <div class="min-w-0">
                    <div class="ci-banner__title">صلاحيات المستخدمين</div>
                    <div class="ci-banner__sub">
                        اختر مستخدماً ثم فعّل الصفحات والخدمات المسموح بها (يُحفظ في بيانات المستخدم)
                    </div>
                </div>
            </div>
        </div>

        <div class="ci-card ci-form-inner ci-user-permissions">
            <div class="ci-card__head ci-card__head-perm">
                <x-filament::icon icon="heroicon-o-key" class="h-5 w-5" />
                <h2>تفاصيل الصلاحيات</h2>
            </div>

            <x-filament-panels::form id="user-permissions-form" wire:submit="save">
                {{ $this->form }}

                <div class="ci-actions">
                    <button class="ci-btn-cancel" type="button" wire:click="cancel">
                        <x-filament::icon icon="heroicon-o-x-circle" class="h-5 w-5" />
                        إلغاء
                    </button>
                    <button class="ci-btn-save" type="submit">
                        <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-5 w-5" />
                        حفظ
                    </button>
                </div>
            </x-filament-panels::form>
        </div>
    </div>
</x-filament-panels::page>
