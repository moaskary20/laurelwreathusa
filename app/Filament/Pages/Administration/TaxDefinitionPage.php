<?php

namespace App\Filament\Pages\Administration;

use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\Tax;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property \Filament\Tables\Table $table
 */
final class TaxDefinitionPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'tax-definition';

    protected static string $view = 'filament.pages.administration.tax-definition';

    protected static ?string $navigationGroup = 'إدارة';

    protected static ?string $title = 'تعريف الضريبة';

    protected static ?string $navigationLabel = 'تعريف الضريبة';

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?int $navigationSort = 7;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return Tax::query()->where('company_id', $tenant->id);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label('الضريبة')
                ->searchable()
                ->sortable()
                ->alignCenter(),
            Tables\Columns\TextColumn::make('rate')
                ->label('نسبة الضريبة')
                ->formatStateUsing(fn (?string $state): string => $state === null ? '—' : $state.'%')
                ->sortable()
                ->alignCenter(),
        ];
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\CreateAction::make()
                ->label('اضافة الضريبة')
                ->icon('heroicon-o-plus')
                ->model(Tax::class)
                ->modalHeading('اضافة الضريبة')
                ->modalWidth(MaxWidth::TwoExtraLarge)
                ->modalSubmitActionLabel('حفظ')
                ->modalCancelActionLabel('إلغاء')
                ->createAnother(false)
                ->mutateFormDataUsing(function (array $data): array {
                    $tenant = Filament::getTenant();
                    abort_unless($tenant instanceof Company, 404);

                    return array_merge($data, [
                        'company_id' => $tenant->id,
                    ]);
                })
                ->successNotificationTitle('تمت إضافة الضريبة')
                ->form(fn (Form $form): Form => $this->taxForm($form)),
        ];
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableActions(): array
    {
        return [
            Tables\Actions\EditAction::make()
                ->label('تعديل')
                ->icon('heroicon-o-pencil')
                ->iconButton()
                ->size(ActionSize::Small)
                ->tooltip('تعديل')
                ->modalHeading('تعديل الضريبة')
                ->modalWidth(MaxWidth::TwoExtraLarge)
                ->modalSubmitActionLabel('حفظ')
                ->modalCancelActionLabel('إلغاء')
                ->successNotificationTitle('تم حفظ التعديلات')
                ->form(fn (Form $form): Form => $this->taxForm($form)),
            Tables\Actions\DeleteAction::make()
                ->label('حذف')
                ->icon('heroicon-o-trash')
                ->iconButton()
                ->size(ActionSize::Small)
                ->tooltip('حذف')
                ->modalHeading('حذف الضريبة')
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
        return 'ضريبة';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'ضرائب';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا يوجد بيانات';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return null;
    }

    public function taxForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('الضريبة')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('account_group_id')
                                    ->label('المجموعات')
                                    ->options(function (): array {
                                        $tenant = Filament::getTenant();
                                        abort_unless($tenant instanceof Company, 404);

                                        return AccountGroup::indentedPostingOptionsForCompany($tenant->id);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                            ]),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('rate')
                                    ->label('نسبة الضريبة')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->required()
                                    ->extraFieldWrapperAttributes(['class' => 'ci-tax-rate-field']),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public function getTitle(): string | Htmlable
    {
        return 'تعريف الضريبة';
    }
}
