<?php

namespace App\Filament\Pages\Payroll;

use App\Models\Company;
use App\Models\PayrollDeduction;
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
final class DeductionsDefinitionPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'deductions-definition-page';

    protected static string $view = 'filament.pages.payroll.deductions-definition-list';

    protected static ?string $navigationGroup = 'كشف الرواتب';

    protected static ?string $title = 'تعريف الاقتطاعات';

    protected static ?string $navigationLabel = 'تعريف الاقتطاعات';

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?int $navigationSort = 3;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return PayrollDeduction::query()->where('company_id', $tenant->id);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('deduction_type')
                ->label('نوع الاقتطاع')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('amount')
                ->label('قيمة الاقتطاع')
                ->numeric(2),
            Tables\Columns\TextColumn::make('frequency')
                ->label('تحسب')
                ->formatStateUsing(function (?string $state): string {
                    $opts = PayrollDeduction::frequencyOptions();

                    return $opts[$state ?? ''] ?? (string) $state;
                })
                ->sortable(),
            Tables\Columns\TextColumn::make('start_date')
                ->label('تاريخ بداية الاقتطاع')
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
                ->label('اضافة الاقتطاع +')
                ->icon('heroicon-o-plus')
                ->url(DeductionDefinitionFormPage::getUrl()),
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
                ->url(fn (PayrollDeduction $record): string => DeductionDefinitionFormPage::getUrl().'?id='.$record->getKey()),
            Tables\Actions\DeleteAction::make()
                ->label('')
                ->tooltip('حذف')
                ->icon('heroicon-o-trash')
                ->iconButton()
                ->size(ActionSize::Small)
                ->modalHeading('حذف الاقتطاع')
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
        return 'اقتطاع';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'اقتطاعات';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا يوجد بيانات';
    }

    public function getTitle(): string|Htmlable
    {
        return 'تعريف الاقتطاعات';
    }
}
