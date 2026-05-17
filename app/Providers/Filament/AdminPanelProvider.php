<?php

namespace App\Providers\Filament;

use App\Models\Company;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->tenant(Company::class)
            ->login()
            ->brandName('النظام المحاسبي')
            ->font('Tajawal', null, GoogleFontProvider::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->darkMode(true, isForced: true)
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => view('filament.partials.theme')->render(),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.partials.sidebar-enhancements-script')->render(),
            )
            ->navigationGroups([
                NavigationGroup::make('إدارة')->collapsed(),
                NavigationGroup::make('العملاء')->collapsed(),
                NavigationGroup::make('الموردين')->collapsed(),
                NavigationGroup::make('المخزون')->collapsed(),
                NavigationGroup::make('المحاسبة')->collapsed(),
                NavigationGroup::make('الموجودات')->collapsed(),
                NavigationGroup::make('كشف الرواتب')->collapsed(),
                NavigationGroup::make('تقارير')->collapsed(),
            ])
            ->collapsibleNavigationGroups()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
