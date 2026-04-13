<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'الصفحة الرئيسية';

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return 'الصفحة الرئيسية';
    }
}
