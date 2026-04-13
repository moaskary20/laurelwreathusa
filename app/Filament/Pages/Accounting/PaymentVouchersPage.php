<?php

namespace App\Filament\Pages\Accounting;

use App\Models\Company;
use App\Models\PaymentVoucher;
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
final class PaymentVouchersPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'payment-vouchers-page';

    protected static string $view = 'filament.pages.accounting.payment-vouchers-list';

    protected static ?string $navigationGroup = 'المحاسبة';

    protected static ?string $title = 'سندات صرف';

    protected static ?string $navigationLabel = 'سندات صرف';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 4;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'payment_date';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return PaymentVoucher::query()
            ->where('company_id', $tenant->id)
            ->with(['supplier', 'preparedBy', 'lines.purchaseInvoice']);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('supplier.name_ar')
                ->label('اسم المورد')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('first_purchase_invoice_number')
                ->label('رقم الفاتورة')
                ->placeholder('—')
                ->sortable(false),
            Tables\Columns\TextColumn::make('payment_date')
                ->label('تاريخ الفاتورة')
                ->date('d/m/Y')
                ->sortable(),
            Tables\Columns\TextColumn::make('total_amount')
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
        $tenant = Filament::getTenant();

        return [
            Tables\Actions\Action::make('print')
                ->label('')
                ->tooltip('طباعه')
                ->icon('heroicon-o-printer')
                ->iconButton()
                ->action(fn () => $this->js('window.print()')),
            Tables\Actions\Action::make('create')
                ->label('اضافة سند صرف +')
                ->icon('heroicon-o-plus')
                ->url(PaymentVoucherFormPage::getUrl(['tenant' => $tenant])),
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
                ->url(fn (PaymentVoucher $record): string => PaymentVoucherFormPage::getUrl(['tenant' => $tenant]).'?id='.$record->getKey()),
        ];
    }

    protected function getTableActionsColumnLabel(): ?string
    {
        return 'خيارات';
    }

    public function getTableModelLabel(): ?string
    {
        return 'سند صرف';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'سندات صرف';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا توجد سندات صرف';
    }

    public function getTitle(): string|Htmlable
    {
        return 'سندات صرف';
    }
}
