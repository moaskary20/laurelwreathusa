<?php

namespace App\Filament\Pages\Inventory;

use App\Models\Company;
use App\Models\FinishedGoodsInwardVoucher;
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
final class FinishedGoodsInwardVoucherPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'finished-goods-inward-voucher-page';

    protected static string $view = 'filament.pages.inventory.finished-goods-inward-voucher-list';

    protected static ?string $navigationGroup = 'المخزون';

    protected static ?string $title = 'سند ادخال انتاج تام';

    protected static ?string $navigationLabel = 'سند ادخال انتاج تام';

    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';

    protected static ?int $navigationSort = 9;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return FinishedGoodsInwardVoucher::query()
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
                ->label('اضافه سند ادخال انتاج تام +')
                ->icon('heroicon-o-plus')
                ->url(FinishedGoodsInwardVoucherFormPage::getUrl()),
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
                ->url(fn (FinishedGoodsInwardVoucher $record): string => FinishedGoodsInwardVoucherFormPage::getUrl().'?id='.$record->getKey()),
        ];
    }

    protected function getTableActionsColumnLabel(): ?string
    {
        return 'خيارات';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا يوجد بيانات';
    }

    public function getTitle(): string|Htmlable
    {
        return 'سند ادخال انتاج تام';
    }
}
