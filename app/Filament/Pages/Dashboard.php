<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CompanySwitcherWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'الصفحة الرئيسية';

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return 'الصفحة الرئيسية';
    }

    /**
     * الـ widgets التي تظهر في أعلى الصفحة الرئيسية.
     * CompanySwitcherWidget يظهر فقط للمستخدمين الذين لديهم صلاحية تبديل الشركات.
     */
    public function getHeaderWidgets(): array
    {
        return [
            CompanySwitcherWidget::class,
        ];
    }
}
