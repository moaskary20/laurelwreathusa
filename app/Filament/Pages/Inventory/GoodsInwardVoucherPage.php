<?php

namespace App\Filament\Pages\Inventory;

use App\Models\Company;
use App\Models\GoodsInwardVoucher;
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
final class GoodsInwardVoucherPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'goods-inward-voucher-page';

    protected static string $view = 'filament.pages.inventory.goods-inward-voucher-list';

    protected static ?string $navigationGroup = 'المخزون';

    protected static ?string $title = 'سند ادخال المواد';

    protected static ?string $navigationLabel = 'سند ادخال المواد';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?int $navigationSort = 6;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return GoodsInwardVoucher::query()
            ->where('company_id', $tenant->id)
            ->with(['preparedBy', 'supplier']);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('supplier.name_ar')
                ->label('اسم المورد')
                ->sortable()
                ->placeholder('—'),
            Tables\Columns\TextColumn::make('voucher_number')
                ->label('رقم الفاتورة')
                ->sortable(),
            Tables\Columns\TextColumn::make('voucher_date')
                ->label('تاريخ الفاتورة')
                ->dateTime('Y-m-d H:i')
                ->sortable(),
            Tables\Columns\TextColumn::make('grand_total')
                ->label('قيمة الفاتورة')
                ->numeric(decimalPlaces: 2)
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
                ->label('اضافة ادخال مواد +')
                ->icon('heroicon-o-plus')
                ->url(GoodsInwardVoucherFormPage::getUrl()),
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
                ->url(fn (GoodsInwardVoucher $record): string => GoodsInwardVoucherFormPage::getUrl().'?id='.$record->getKey()),
        ];
    }

    protected function getTableActionsColumnLabel(): ?string
    {
        return 'خيارات';
    }

    public function getTableModelLabel(): ?string
    {
        return 'سند إدخال';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'سندات إدخال';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا توجد سندات';
    }

    public function getTitle(): string|Htmlable
    {
        return 'سند ادخال المواد';
    }
}
