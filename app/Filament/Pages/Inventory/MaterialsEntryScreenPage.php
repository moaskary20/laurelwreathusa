<?php

namespace App\Filament\Pages\Inventory;

use App\Filament\Pages\SectionPlaceholderPage;

final class MaterialsEntryScreenPage extends SectionPlaceholderPage
{
    protected static ?string $navigationGroup = 'المخزون';

    protected static ?string $title = 'شاشة ادخال المواد';

    protected static ?string $navigationLabel = 'شاشة ادخال المواد';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 7;
}
