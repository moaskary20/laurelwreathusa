<?php

namespace App\Filament\Pages\Payroll;

use App\Filament\Pages\SectionPlaceholderPage;

final class PayrollStatementSecondPage extends SectionPlaceholderPage
{
    protected static ?string $navigationGroup = 'كشف الرواتب';

    protected static ?string $title = 'كشف الرواتب';

    protected static ?string $navigationLabel = 'كشف الرواتب (2)';

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?int $navigationSort = 5;
}
