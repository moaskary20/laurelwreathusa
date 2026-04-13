<?php

namespace App\Filament\Pages\Accounting;

use App\Filament\Pages\SectionPlaceholderPage;

final class CloseOrOpenMonthPage extends SectionPlaceholderPage
{
    protected static ?string $navigationGroup = 'المحاسبة';

    protected static ?string $title = 'اغلاق او فتح شهر';

    protected static ?string $navigationLabel = 'اغلاق او فتح شهر';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 11;
}
