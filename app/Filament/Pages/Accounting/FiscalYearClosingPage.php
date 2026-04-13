<?php

namespace App\Filament\Pages\Accounting;

use App\Filament\Pages\SectionPlaceholderPage;

final class FiscalYearClosingPage extends SectionPlaceholderPage
{
    protected static ?string $navigationGroup = 'المحاسبة';

    protected static ?string $title = 'إغلاق السنة المالية';

    protected static ?string $navigationLabel = 'إغلاق السنة المالية';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 12;
}
