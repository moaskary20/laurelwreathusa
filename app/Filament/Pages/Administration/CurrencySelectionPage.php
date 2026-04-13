<?php

namespace App\Filament\Pages\Administration;

use App\Models\Company;
use App\Models\Currency;
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
final class CurrencySelectionPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'currency-selection';

    protected static string $view = 'filament.pages.administration.currency-selection';

    protected static ?string $navigationGroup = 'إدارة';

    protected static ?string $title = 'اختيار العملة';

    protected static ?string $navigationLabel = 'اختيار العملة';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 6;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return Currency::query()->where('company_id', $tenant->id);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name_ar')
                ->label('اسم العملة')
                ->searchable()
                ->sortable()
                ->alignCenter(),
            Tables\Columns\TextColumn::make('exchange_rate')
                ->label('سعر الصرف')
                ->numeric(6)
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
                ->label('اضافه عملات')
                ->icon('heroicon-o-plus')
                ->model(Currency::class)
                ->modalHeading('اضافه عملات')
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
                ->successNotificationTitle('تمت إضافة العملة')
                ->form(fn (Form $form): Form => $this->currencyForm($form)),
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
                ->modalHeading('تعديل عملة')
                ->modalWidth(MaxWidth::TwoExtraLarge)
                ->modalSubmitActionLabel('حفظ')
                ->modalCancelActionLabel('إلغاء')
                ->successNotificationTitle('تم حفظ التعديلات')
                ->form(fn (Form $form): Form => $this->currencyForm($form)),
            Tables\Actions\DeleteAction::make()
                ->label('حذف')
                ->icon('heroicon-o-trash')
                ->iconButton()
                ->size(ActionSize::Small)
                ->tooltip('حذف')
                ->modalHeading('حذف العملة')
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
        return 'عملة';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'عملات';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا توجد عملات';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'اضغط «اضافه عملات» لإضافة عملة وسعر صرف.';
    }

    public function currencyForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('name_ar')
                                    ->label('اسم العملة')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('exchange_rate')
                                    ->label('سعر الصرف')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->step(0.000001)
                                    ->extraFieldWrapperAttributes(['class' => 'ci-currency-rate-field']),
                            ]),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('name_en')
                                    ->label('اسم العملة بالانجليزي')
                                    ->maxLength(255),
                                Forms\Components\Checkbox::make('is_main')
                                    ->label('عملة رئيسية'),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public function getTitle(): string | Htmlable
    {
        return 'اختيار العملة';
    }
}
