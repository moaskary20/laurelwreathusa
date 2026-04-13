<?php

namespace App\Filament\Pages\Customers;

use App\Filament\Pages\SectionPlaceholderPage;

final class AnalyticalPage extends SectionPlaceholderPage
{
    protected static ?string $navigationGroup = 'العملاء';

    protected static ?string $title = 'تحليلي';

    protected static ?string $navigationLabel = 'تحليلي';

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?int $navigationSort = 7;
}
