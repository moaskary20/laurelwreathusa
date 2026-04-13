<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    protected static string $view = 'filament.resources.company-resource.pages.create-company';

    protected static ?string $title = 'إضافة شركة';

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getRedirectUrl(): string
    {
        return Filament::getUrl($this->getRecord());
    }
}
