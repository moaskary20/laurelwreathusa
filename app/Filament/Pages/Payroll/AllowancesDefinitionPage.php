<?php

namespace App\Filament\Pages\Payroll;

use App\Models\Company;
use App\Models\PayrollAllowance;
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
final class AllowancesDefinitionPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'allowances-definition-page';

    protected static string $view = 'filament.pages.payroll.allowances-definition-list';

    protected static ?string $navigationGroup = 'كشف الرواتب';

    protected static ?string $title = 'تعريف العلاوات';

    protected static ?string $navigationLabel = 'تعريف العلاوات';

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?int $navigationSort = 2;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return PayrollAllowance::query()->where('company_id', $tenant->id);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('allowance_type')
                ->label('نوع العلاوة')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('amount')
                ->label('قيمه العلاوة')
                ->numeric(2),
            Tables\Columns\TextColumn::make('frequency')
                ->label('تحسب')
                ->formatStateUsing(function (?string $state): string {
                    $opts = PayrollAllowance::frequencyOptions();

                    return $opts[$state ?? ''] ?? (string) $state;
                })
                ->sortable(),
            Tables\Columns\TextColumn::make('start_date')
                ->label('تاريخ بداية العلاوة')
                ->date('Y-m-d')
                ->sortable(),
        ];
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\Action::make('create')
                ->label('اضافة العلاوة')
                ->icon('heroicon-o-plus')
                ->url(AllowanceDefinitionFormPage::getUrl()),
        ];
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('edit')
                ->label('')
                ->tooltip('تعديل')
                ->icon('heroicon-o-pencil')
                ->iconButton()
                ->size(ActionSize::Small)
                ->url(fn (PayrollAllowance $record): string => AllowanceDefinitionFormPage::getUrl().'?id='.$record->getKey()),
            Tables\Actions\DeleteAction::make()
                ->label('')
                ->tooltip('حذف')
                ->icon('heroicon-o-trash')
                ->iconButton()
                ->size(ActionSize::Small)
                ->modalHeading('حذف العلاوة')
                ->modalSubmitActionLabel('حذف')
                ->modalCancelActionLabel('إلغاء')
                ->successNotificationTitle('تم الحذف'),
        ];
    }

    protected function getTableActionsColumnLabel(): ?string
    {
        return 'خيارات';
    }

    public function getTableModelLabel(): ?string
    {
        return 'علاوة';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'علاوات';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا توجد علاوات';
    }

    public function getTitle(): string|Htmlable
    {
        return 'تعريف العلاوات';
    }
}
