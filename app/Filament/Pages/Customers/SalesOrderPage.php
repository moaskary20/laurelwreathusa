<?php

namespace App\Filament\Pages\Customers;

use App\Models\Company;
use App\Models\SalesOrder;
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
final class SalesOrderPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'sales-order-page';

    protected static string $view = 'filament.pages.customers.sales-order-list';

    protected static ?string $navigationGroup = 'العملاء';

    protected static ?string $title = 'امر البيع';

    protected static ?string $navigationLabel = 'امر البيع';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 5;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return SalesOrder::query()
            ->where('company_id', $tenant->id)
            ->with(['customer', 'preparedBy']);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('customer.name_ar')
                ->label('اسم العميل')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('order_number')
                ->label('رقم امر البيع')
                ->sortable(),
            Tables\Columns\TextColumn::make('order_date')
                ->label('تاريخ امر البيع')
                ->dateTime('Y-m-d H:i')
                ->sortable(),
            Tables\Columns\TextColumn::make('total_value')
                ->label('قيمة امر البيع')
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
        $tenant = Filament::getTenant();

        return [
            Tables\Actions\Action::make('print')
                ->label('')
                ->tooltip('طباعه')
                ->icon('heroicon-o-printer')
                ->iconButton()
                ->action(fn () => $this->js('window.print()')),
            Tables\Actions\Action::make('create')
                ->label('اضافة امر بيع +')
                ->icon('heroicon-o-plus')
                ->url(SalesOrderFormPage::getUrl(['tenant' => $tenant])),
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
                ->url(fn (SalesOrder $record): string => SalesOrderFormPage::getUrl(['tenant' => $tenant]).'?id='.$record->getKey()),
        ];
    }

    protected function getTableActionsColumnLabel(): ?string
    {
        return 'خيارات';
    }

    public function getTableModelLabel(): ?string
    {
        return 'امر بيع';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'اوامر بيع';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا توجد اوامر بيع';
    }

    public function getTitle(): string|Htmlable
    {
        return 'امر البيع';
    }
}
