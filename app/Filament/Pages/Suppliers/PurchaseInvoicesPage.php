<?php

namespace App\Filament\Pages\Suppliers;

use App\Models\Company;
use App\Models\PurchaseInvoice;
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
final class PurchaseInvoicesPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'purchase-invoices-page';

    protected static string $view = 'filament.pages.suppliers.purchase-invoices-list';

    protected static ?string $navigationGroup = 'الموردين';

    protected static ?string $title = 'فواتير المشتريات';

    protected static ?string $navigationLabel = 'فواتير المشتريات';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 4;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return PurchaseInvoice::query()
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
            Tables\Columns\TextColumn::make('invoice_number')
                ->label('رقم الفاتورة')
                ->sortable(),
            Tables\Columns\TextColumn::make('invoice_date')
                ->label('تاريخ الفاتورة')
                ->date('d/m/Y')
                ->sortable(),
            Tables\Columns\TextColumn::make('grand_total')
                ->label('قيمة الفاتورة')
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
                ->label('اضافه فاتورة +')
                ->icon('heroicon-o-plus')
                ->url(PurchaseInvoiceFormPage::getUrl()),
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
                ->url(fn (PurchaseInvoice $record): string => PurchaseInvoiceFormPage::getUrl().'?id='.$record->getKey()),
        ];
    }

    protected function getTableActionsColumnLabel(): ?string
    {
        return 'خيارات';
    }

    public function getTableModelLabel(): ?string
    {
        return 'فاتورة مشتريات';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'فواتير مشتريات';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا توجد فواتير مشتريات';
    }

    public function getTitle(): string|Htmlable
    {
        return 'فواتير المشتريات';
    }
}
