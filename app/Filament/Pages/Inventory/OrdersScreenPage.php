<?php

namespace App\Filament\Pages\Inventory;

use App\Models\Company;
use App\Models\InventoryOrder;
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
final class OrdersScreenPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'orders-screen-page';

    protected static string $view = 'filament.pages.inventory.orders-screen-list';

    protected static ?string $navigationGroup = 'المخزون';

    protected static ?string $title = 'شاشة الطلبيات';

    protected static ?string $navigationLabel = 'شاشة الطلبيات';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 4;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return InventoryOrder::query()
            ->where('company_id', $tenant->id)
            ->with(['preparedBy']);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('order_number')
                ->label('رقم الطلبية')
                ->sortable(),
            Tables\Columns\TextColumn::make('order_date')
                ->label('تاريخ الطلبية')
                ->dateTime('Y-m-d H:i')
                ->sortable(),
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
        return [
            Tables\Actions\Action::make('create')
                ->label('اضافة طلبية +')
                ->icon('heroicon-o-plus')
                ->url(InventoryOrderFormPage::getUrl()),
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
                ->url(fn (InventoryOrder $record): string => InventoryOrderFormPage::getUrl().'?id='.$record->getKey()),
        ];
    }

    protected function getTableActionsColumnLabel(): ?string
    {
        return 'خيارات';
    }

    public function getTableModelLabel(): ?string
    {
        return 'طلبية';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'طلبيات';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا توجد طلبيات';
    }

    public function getTitle(): string|Htmlable
    {
        return 'شاشة الطلبيات';
    }
}
