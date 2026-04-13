<?php

namespace App\Filament\Pages\Accounting;

use App\Filament\Pages\SectionPlaceholderPage;

final class AgingBalancesPage extends SectionPlaceholderPage
{
    protected static ?string $navigationGroup = 'المحاسبة';

    protected static ?string $title = 'اعمار الذمم';

    protected static ?string $navigationLabel = 'اعمار الذمم';

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?int $navigationSort = 10;
}
