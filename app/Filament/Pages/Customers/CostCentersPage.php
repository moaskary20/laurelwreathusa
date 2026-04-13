<?php

namespace App\Filament\Pages\Customers;

use App\Models\Company;
use App\Models\CostCenter;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

/**
 * @property Table $table
 */
final class CostCentersPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'cost-centers-page';

    protected static string $view = 'filament.pages.customers.cost-centers-list';

    protected static ?string $navigationGroup = 'العملاء';

    protected static ?string $title = 'مراكز التكلفه';

    protected static ?string $navigationLabel = 'مراكز التكلفه';

    protected static ?string $navigationIcon = 'heroicon-o-view-columns';

    protected static ?int $navigationSort = 6;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return CostCenter::query()->where('company_id', $tenant->id);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name_ar')
                ->label('اسم مركز التكلفه بالعربي')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('name_en')
                ->label('اسم مركز التكلفه انجليزي')
                ->searchable()
                ->placeholder('—'),
            Tables\Columns\TextColumn::make('code')
                ->label('كود مركز التكلفه')
                ->searchable()
                ->sortable()
                ->placeholder('—'),
        ];
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\CreateAction::make()
                ->label('اضافه مراكز التكلفه +')
                ->icon('heroicon-o-plus')
                ->model(CostCenter::class)
                ->modalHeading('مركز التكلفة')
                ->modalWidth(MaxWidth::TwoExtraLarge)
                ->modalSubmitActionLabel('حفظ')
                ->modalCancelActionLabel('إلغاء')
                ->createAnother(false)
                ->mutateFormDataUsing(function (array $data): array {
                    $tenant = Filament::getTenant();
                    abort_unless($tenant instanceof Company, 404);

                    $data['company_id'] = $tenant->id;

                    return $data;
                })
                ->successNotificationTitle('تمت الإضافة')
                ->form(fn (Form $form): Form => $this->costCenterForm($form)),
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
                ->modalHeading('مركز التكلفة')
                ->modalWidth(MaxWidth::TwoExtraLarge)
                ->modalSubmitActionLabel('حفظ')
                ->modalCancelActionLabel('إلغاء')
                ->successNotificationTitle('تم حفظ التعديلات')
                ->form(fn (Form $form): Form => $this->costCenterForm($form)),
            Tables\Actions\DeleteAction::make()
                ->label('حذف')
                ->icon('heroicon-o-trash')
                ->iconButton()
                ->size(ActionSize::Small)
                ->tooltip('حذف')
                ->modalHeading('حذف مركز التكلفة')
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
        return 'مركز تكلفة';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'مراكز تكلفة';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا توجد مراكز تكلفة';
    }

    public function costCenterForm(Form $form): Form
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('name_ar')
                            ->label('اسم مركز التكلفة بالعربي')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name_en')
                            ->label('اسم مركز التكلفة بالانجليزي')
                            ->maxLength(255),
                    ]),
                Forms\Components\TextInput::make('code')
                    ->label('كود مركز التكلفة')
                    ->required()
                    ->maxLength(50)
                    ->unique(
                        table: 'cost_centers',
                        column: 'code',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('company_id', $tenant->id),
                    ),
            ])
            ->columns(1);
    }

    public function getTitle(): string|Htmlable
    {
        return 'مراكز التكلفه';
    }
}
