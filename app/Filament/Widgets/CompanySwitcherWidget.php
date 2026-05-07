<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use Filament\Widgets\Widget;

class CompanySwitcherWidget extends Widget
{
    protected static string $view = 'filament.widgets.company-switcher';

    /** منع الاكتشاف التلقائي لتجنب ظهورها مرتين */
    protected static bool $isDiscovered = false;

    /** تظهر في أعلى لوحة التحكم */
    protected static ?int $sort = -10;

    /** تمتد على كامل العرض */
    protected int | string | array $columnSpan = 'full';

    public string $selectedCompanyId = '';

    public function mount(): void
    {
        $tenant = filament()->getTenant();
        $this->selectedCompanyId = (string) ($tenant?->id ?? '');
    }

    public function getCompanies(): \Illuminate\Support\Collection
    {
        return Company::query()->orderBy('trade_name')->get();
    }

    public function switchCompany(): void
    {
        if ($this->selectedCompanyId === '') {
            return;
        }

        $company = Company::find($this->selectedCompanyId);

        if ($company && auth()->user()?->canAccessTenant($company)) {
            $this->redirect('/admin/' . $this->selectedCompanyId);
        }
    }

    /** لا تظهر إلا للمستخدمين الذين يملكون صلاحية تبديل الشركات */
    public static function canView(): bool
    {
        return auth()->user()?->isAdminTenantSwitcher() ?? false;
    }
}
