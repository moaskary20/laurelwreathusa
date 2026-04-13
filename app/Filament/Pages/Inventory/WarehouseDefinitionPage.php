<?php

namespace App\Filament\Pages\Inventory;

use App\Models\Company;
use App\Models\Warehouse;
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
final class WarehouseDefinitionPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'warehouse-definition-page';

    protected static string $view = 'filament.pages.inventory.warehouse-definition-list';

    protected static ?string $navigationGroup = 'المخزون';

    protected static ?string $title = 'تعريف المستودعات';

    protected static ?string $navigationLabel = 'تعريف المستودعات';

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 1;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return Warehouse::query()->where('company_id', $tenant->id);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name_ar')
                ->label('اسم المستودع عربي')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('name_en')
                ->label('اسم المستودع انجليزي')
                ->searchable()
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
                ->label('اضافه المستودعات +')
                ->icon('heroicon-o-plus')
                ->model(Warehouse::class)
                ->modalHeading('اضافة المستودع')
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
                ->form(fn (Form $form): Form => $this->warehouseForm($form)),
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
                ->modalHeading('تعديل المستودع')
                ->modalWidth(MaxWidth::TwoExtraLarge)
                ->modalSubmitActionLabel('حفظ')
                ->modalCancelActionLabel('إلغاء')
                ->successNotificationTitle('تم حفظ التعديلات')
                ->form(fn (Form $form): Form => $this->warehouseForm($form)),
            Tables\Actions\DeleteAction::make()
                ->label('حذف')
                ->icon('heroicon-o-trash')
                ->iconButton()
                ->size(ActionSize::Small)
                ->tooltip('حذف')
                ->modalHeading('حذف المستودع')
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
        return 'مستودع';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'مستودعات';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا توجد مستودعات';
    }

    public function warehouseForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('name_ar')
                            ->label('اسم المستودع بالعربي')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name_en')
                            ->label('اسم المستودع بالانجليزي')
                            ->maxLength(255),
                    ]),
            ])
            ->columns(1);
    }

    public function getTitle(): string|Htmlable
    {
        return 'تعريف المستودعات';
    }
}
