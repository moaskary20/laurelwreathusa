<?php

namespace App\Filament\Pages\Inventory;

use App\Models\Company;
use App\Models\GoodsOutwardVoucher;
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
final class GoodsOutwardVoucherPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'goods-outward-voucher-page';

    protected static string $view = 'filament.pages.inventory.goods-outward-voucher-list';

    protected static ?string $navigationGroup = 'المخزون';

    protected static ?string $title = 'سند اخراج بضاعه';

    protected static ?string $navigationLabel = 'سند اخراج بضاعه';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?int $navigationSort = 5;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return GoodsOutwardVoucher::query()
            ->where('company_id', $tenant->id)
            ->with(['preparedBy']);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('voucher_number')
                ->label('رقم الفاتورة')
                ->sortable(),
            Tables\Columns\TextColumn::make('voucher_date')
                ->label('تاريخ الفاتورة')
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
                ->label('اضافه سند اخراج +')
                ->icon('heroicon-o-plus')
                ->url(GoodsOutwardVoucherFormPage::getUrl()),
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
                ->url(fn (GoodsOutwardVoucher $record): string => GoodsOutwardVoucherFormPage::getUrl().'?id='.$record->getKey()),
        ];
    }

    protected function getTableActionsColumnLabel(): ?string
    {
        return 'خيارات';
    }

    public function getTableModelLabel(): ?string
    {
        return 'سند إخراج';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'سندات إخراج';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا توجد سندات';
    }

    public function getTitle(): string|Htmlable
    {
        return 'سند اخراج بضاعه';
    }
}
