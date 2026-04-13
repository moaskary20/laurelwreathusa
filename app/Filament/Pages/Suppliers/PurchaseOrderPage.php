<?php

namespace App\Filament\Pages\Suppliers;

use App\Models\Company;
use App\Models\PurchaseOrder;
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
final class PurchaseOrderPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'purchase-order-page';

    protected static string $view = 'filament.pages.suppliers.purchase-order-list';

    protected static ?string $navigationGroup = 'الموردين';

    protected static ?string $title = 'امر الشراء';

    protected static ?string $navigationLabel = 'امر الشراء';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 3;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return PurchaseOrder::query()
            ->where('company_id', $tenant->id)
            ->with(['supplier', 'preparedBy']);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('supplier.name_ar')
                ->label('اسم المورد بالعربي')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('order_number')
                ->label('رقم امر الشراء')
                ->sortable(),
            Tables\Columns\TextColumn::make('order_date')
                ->label('تاريخ امر الشراء')
                ->dateTime('Y-m-d H:i')
                ->sortable(),
            Tables\Columns\TextColumn::make('total_value')
                ->label('قيمة امر الشراء')
                ->numeric(2),
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
            Tables\Actions\Action::make('print')
                ->label('')
                ->tooltip('طباعه')
                ->icon('heroicon-o-printer')
                ->iconButton()
                ->action(fn () => $this->js('window.print()')),
            Tables\Actions\Action::make('create')
                ->label('اضافة امر الشراء +')
                ->icon('heroicon-o-plus')
                ->url(PurchaseOrderFormPage::getUrl()),
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
                ->url(fn (PurchaseOrder $record): string => PurchaseOrderFormPage::getUrl().'?id='.$record->getKey()),
        ];
    }

    protected function getTableActionsColumnLabel(): ?string
    {
        return 'خيارات';
    }

    public function getTableModelLabel(): ?string
    {
        return 'امر شراء';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'اوامر شراء';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا توجد اوامر شراء';
    }

    public function getTitle(): string|Htmlable
    {
        return 'امر الشراء';
    }
}
