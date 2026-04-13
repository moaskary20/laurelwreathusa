<?php

namespace App\Filament\Resources\OfficeResource\Pages;

use App\Filament\Resources\OfficeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOffice extends CreateRecord
{
    protected static string $resource = OfficeResource::class;

    protected static string $view = 'filament.resources.office-resource.pages.create-office';

    protected static ?string $title = 'إضافة مكتب';

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
