<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

abstract class SectionPlaceholderPage extends Page
{
    protected static string $view = 'filament.pages.section-placeholder';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
}
