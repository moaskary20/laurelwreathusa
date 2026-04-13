<?php

namespace App\Filament\Pages\Accounting;

use App\Models\Company;
use App\Models\CreditNote;
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
final class CreditNotePage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'credit-note-page';

    protected static string $view = 'filament.pages.accounting.credit-note-list';

    protected static ?string $navigationGroup = 'المحاسبة';

    protected static ?string $title = 'اشعار دائن';

    protected static ?string $navigationLabel = 'اشعار دائن';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 6;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'document_date';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return CreditNote::query()
            ->where('company_id', $tenant->id)
            ->with(['customer', 'supplier', 'preparedBy']);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('customer_col')
                ->label('اسم العميل')
                ->getStateUsing(fn (CreditNote $record): string => $record->counterparty_type === CreditNote::TYPE_CUSTOMER
                    ? ($record->customer?->name_ar ?? '—')
                    : '—'),
            Tables\Columns\TextColumn::make('supplier_col')
                ->label('اسم المورد')
                ->getStateUsing(fn (CreditNote $record): string => $record->counterparty_type === CreditNote::TYPE_SUPPLIER
                    ? ($record->supplier?->name_ar ?? '—')
                    : '—'),
            Tables\Columns\TextColumn::make('document_number')
                ->label('رقم الإشعار الدائن')
                ->sortable(),
            Tables\Columns\TextColumn::make('document_date')
                ->label('التاريخ')
                ->date('d/m/Y')
                ->sortable(),
            Tables\Columns\TextColumn::make('amount')
                ->label('المبلغ')
                ->numeric(2),
            Tables\Columns\TextColumn::make('preparedBy.name')
                ->label('الشخص الذي أعد الفاتورة')
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
                ->label('إشعار دائن +')
                ->icon('heroicon-o-plus')
                ->url(CreditNoteFormPage::getUrl(['tenant' => $tenant])),
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
                ->url(fn (CreditNote $record): string => CreditNoteFormPage::getUrl(['tenant' => $tenant]).'?id='.$record->getKey()),
        ];
    }

    protected function getTableActionsColumnLabel(): ?string
    {
        return 'خيارات';
    }

    public function getTableModelLabel(): ?string
    {
        return 'إشعار دائن';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'إشعارات دائنة';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا يوجد بيانات';
    }

    public function getTitle(): string|Htmlable
    {
        return 'اشعار دائن';
    }
}
