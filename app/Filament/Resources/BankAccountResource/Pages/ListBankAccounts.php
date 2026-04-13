<?php

namespace App\Filament\Resources\BankAccountResource\Pages;

use App\Filament\Resources\BankAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBankAccounts extends ListRecords
{
    protected static string $resource = BankAccountResource::class;

    protected static string $view = 'filament.resources.bank-account-resource.pages.list-bank-accounts';

    protected static ?string $title = 'بيانات البنك';

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('اضافة بيانات البنك'),
        ];
    }
}
