<?php

namespace App\Filament\Pages\Accounting;

use App\Models\Company;
use App\Models\JournalEntry;
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
final class JournalEntriesPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'journal-entries-page';

    protected static string $view = 'filament.pages.accounting.journal-entries-list';

    protected static ?string $navigationGroup = 'المحاسبة';

    protected static ?string $title = 'القيود';

    protected static ?string $navigationLabel = 'القيود';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 2;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'entry_date';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return JournalEntry::query()
            ->where('company_id', $tenant->id)
            ->with(['preparedBy']);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('entry_number')
                ->label('رقم القيد')
                ->sortable(),
            Tables\Columns\TextColumn::make('entry_date')
                ->label('تاريخ القيد')
                ->date('d/m/Y')
                ->sortable(),
            Tables\Columns\TextColumn::make('preparedBy.name')
                ->label('المستخدمين')
                ->placeholder('—'),
            Tables\Columns\TextColumn::make('title')
                ->label('عنوان القيد')
                ->searchable()
                ->wrap(),
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
                ->label('اضافه قيد +')
                ->icon('heroicon-o-plus')
                ->url(JournalEntryFormPage::getUrl(['tenant' => $tenant])),
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
                ->url(fn (JournalEntry $record): string => JournalEntryFormPage::getUrl(['tenant' => $tenant]).'?id='.$record->getKey()),
        ];
    }

    protected function getTableActionsColumnLabel(): ?string
    {
        return 'خيارات';
    }

    public function getTableModelLabel(): ?string
    {
        return 'قيد';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'قيود';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا توجد قيود';
    }

    public function getTitle(): string|Htmlable
    {
        return 'القيود';
    }
}
