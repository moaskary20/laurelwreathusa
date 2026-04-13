<?php

namespace App\Filament\Pages\Inventory;

use App\Models\Company;
use App\Models\WarehouseRequisition;
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
final class WarehouseRequisitionPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'warehouse-requisition-page';

    protected static string $view = 'filament.pages.inventory.warehouse-requisition-list';

    protected static ?string $navigationGroup = 'المخزون';

    protected static ?string $title = 'طلب صرف مستودع';

    protected static ?string $navigationLabel = 'طلب صرف مستودع';

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?int $navigationSort = 10;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return WarehouseRequisition::query()
            ->where('company_id', $tenant->id)
            ->with(['preparedBy']);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('request_number')
                ->label('رقم السند')
                ->sortable(),
            Tables\Columns\TextColumn::make('request_date')
                ->label('تاريخ طلب صرف مستودع')
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
                ->label('طلب صرف بضاعة +')
                ->icon('heroicon-o-plus')
                ->url(WarehouseRequisitionFormPage::getUrl()),
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
                ->url(fn (WarehouseRequisition $record): string => WarehouseRequisitionFormPage::getUrl().'?id='.$record->getKey()),
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
        return 'طلب صرف مستودع';
    }
}
