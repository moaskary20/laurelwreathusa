<?php

namespace App\Filament\Pages\Accounting;

use App\Filament\Pages\SectionPlaceholderPage;

final class ChartOfAccountsPage extends SectionPlaceholderPage
{
    protected static ?string $navigationGroup = 'المحاسبة';

    protected static ?string $title = 'شجرة الحسابات';

    protected static ?string $navigationLabel = 'شجرة الحسابات';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 1;
}
