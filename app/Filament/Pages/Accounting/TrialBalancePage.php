<?php

namespace App\Filament\Pages\Accounting;

use App\Filament\Pages\SectionPlaceholderPage;

final class TrialBalancePage extends SectionPlaceholderPage
{
    protected static ?string $navigationGroup = 'المحاسبة';

    protected static ?string $title = 'ميزان المراجعة';

    protected static ?string $navigationLabel = 'ميزان المراجعة';

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?int $navigationSort = 9;
}
