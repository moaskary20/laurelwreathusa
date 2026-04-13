<?php

namespace App\Filament\Pages\Accounting;

use App\Models\BankDeposit;
use App\Models\Company;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Enums\ActionSize;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property Table $table
 */
final class BankDepositPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'bank-deposit-page';

    protected static string $view = 'filament.pages.accounting.bank-deposit-list';

    protected static ?string $navigationGroup = 'المحاسبة';

    protected static ?string $title = 'ايداع بنكي';

    protected static ?string $navigationLabel = 'ايداع بنكي';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 7;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'deposit_date';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return BankDeposit::query()
            ->where('company_id', $tenant->id)
            ->with(['preparedBy']);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('deposit_number')
                ->label('رقم الايداع')
                ->sortable(),
            Tables\Columns\TextColumn::make('deposit_date')
                ->label('التاريخ')
                ->date('d/m/Y')
                ->sortable(),
            Tables\Columns\TextColumn::make('amount')
                ->label('المبلغ')
                ->numeric(2),
            Tables\Columns\TextColumn::make('preparedBy.name')
                ->label('الشخص الذي اعد الفاتورة')
                ->placeholder('—'),
        ];
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableHeaderActions(): array
    {
        $tenant = Filament::getTenant();

        return [
            Tables\Actions\Action::make('print')
                ->label('')
                ->tooltip('طباعه')
                ->icon('heroicon-o-printer')
                ->iconButton()
                ->action(fn () => $this->js('window.print()')),
            Tables\Actions\Action::make('create')
                ->label('إيداع بنكي +')
                ->icon('heroicon-o-plus')
                ->url(BankDepositFormPage::getUrl(['tenant' => $tenant])),
        ];
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableActions(): array
    {
        $tenant = Filament::getTenant();

        return [
            Tables\Actions\Action::make('edit')
                ->label('')
                ->tooltip('تعديل')
                ->icon('heroicon-o-pencil')
                ->iconButton()
                ->size(ActionSize::Small)
                ->url(fn (BankDeposit $record): string => BankDepositFormPage::getUrl(['tenant' => $tenant]).'?id='.$record->getKey()),
        ];
    }

    protected function getTableActionsColumnLabel(): ?string
    {
        return 'خيارات';
    }

    public function getTableModelLabel(): ?string
    {
        return 'إيداع بنكي';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'إيداعات بنكية';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا يوجد بيانات';
    }

    public function getTitle(): string|Htmlable
    {
        return 'ايداع بنكي';
    }
}
