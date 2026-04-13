<?php

namespace App\Filament\Pages\Inventory;

use App\Models\Company;
use App\Models\MeasurementUnit;
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

/**
 * @property Table $table
 */
final class UnitOfMeasurementDefinitionPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'unit-of-measurement-definition-page';

    protected static string $view = 'filament.pages.inventory.unit-of-measurement-definition-list';

    protected static ?string $navigationGroup = 'المخزون';

    protected static ?string $title = 'تعريف وحدة القياس';

    protected static ?string $navigationLabel = 'تعريف وحدة القياس';

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?int $navigationSort = 2;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return MeasurementUnit::query()->where('company_id', $tenant->id);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name_ar')
                ->label('اسم الوحدة')
                ->searchable()
                ->sortable(),
        ];
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\CreateAction::make()
                ->label('اضافة الوحدة +')
                ->icon('heroicon-o-plus')
                ->model(MeasurementUnit::class)
                ->modalHeading('تعريف وحدة القياس')
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
                ->form(fn (Form $form): Form => $this->measurementUnitForm($form)),
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
                ->modalHeading('تعريف وحدة القياس')
                ->modalWidth(MaxWidth::TwoExtraLarge)
                ->modalSubmitActionLabel('حفظ')
                ->modalCancelActionLabel('إلغاء')
                ->successNotificationTitle('تم حفظ التعديلات')
                ->form(fn (Form $form): Form => $this->measurementUnitForm($form)),
            Tables\Actions\DeleteAction::make()
                ->label('حذف')
                ->icon('heroicon-o-trash')
                ->iconButton()
                ->size(ActionSize::Small)
                ->tooltip('حذف')
                ->modalHeading('حذف وحدة القياس')
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
        return 'وحدة قياس';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'وحدات قياس';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا توجد وحدات قياس';
    }

    public function measurementUnitForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('name_ar')
                            ->label('اسم الوحدة بالعربي')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name_en')
                            ->label('اسم الوحدة بالانجليزي')
                            ->maxLength(255),
                    ]),
            ])
            ->columns(1);
    }

    public function getTitle(): string|Htmlable
    {
        return 'تعريف وحدة القياس';
    }
}
