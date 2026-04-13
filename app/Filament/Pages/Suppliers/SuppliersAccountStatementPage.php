<?php

namespace App\Filament\Pages\Suppliers;

use App\Models\Company;
use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property Table $table
 */
final class SuppliersAccountStatementPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'suppliers-account-statement-page';

    protected static string $view = 'filament.pages.suppliers.suppliers-account-statement';

    protected static ?string $navigationGroup = 'الموردين';

    protected static ?string $title = 'كشف حساب الموردين';

    protected static ?string $navigationLabel = 'كشف حساب الموردين';

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?int $navigationSort = 2;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    /** @var array<int, float> */
    public array $runningBalances = [];

    public bool $statementReady = false;

    public ?string $selectedSupplierName = null;

    public ?string $selectedSupplierCode = null;

    /** @var array<string, mixed> */
    public ?array $filterData = [];

    public function mount(): void
    {
        $tenant = Filament::getTenant();
        $supplierId = request()->integer('supplier_id') ?: null;

        if ($supplierId !== null && $tenant instanceof Company) {
            $exists = Supplier::query()
                ->where('company_id', $tenant->id)
                ->whereKey($supplierId)
                ->exists();
            if (! $exists) {
                $supplierId = null;
            }
        } elseif (! $tenant instanceof Company) {
            $supplierId = null;
        }

        $this->filterData = [
            'supplier_id' => $supplierId,
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ];

        if ($supplierId !== null) {
            $this->searchStatement();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('supplier_id')
                    ->label('اختر')
                    ->options(function (): array {
                        $tenant = Filament::getTenant();
                        abort_unless($tenant instanceof Company, 404);

                        return Supplier::query()
                            ->where('company_id', $tenant->id)
                            ->orderBy('name_ar')
                            ->pluck('name_ar', 'id')
                            ->all();
                    })
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required(),
                Forms\Components\DatePicker::make('date_from')
                    ->label('التاريخ من')
                    ->native(false)
                    ->displayFormat('Y/m/d')
                    ->required(),
                Forms\Components\DatePicker::make('date_to')
                    ->label('إلى')
                    ->native(false)
                    ->displayFormat('Y/m/d')
                    ->required(),
            ])
            ->columns(3)
            ->statePath('filterData');
    }

    public function searchStatement(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $this->validate([
            'filterData.supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where('company_id', $tenant->id),
            ],
            'filterData.date_from' => 'required|date',
            'filterData.date_to' => 'required|date|after_or_equal:filterData.date_from',
        ], attributes: [
            'filterData.supplier_id' => 'المورد',
            'filterData.date_from' => 'التاريخ من',
            'filterData.date_to' => 'إلى',
        ]);

        $supplier = Supplier::query()
            ->where('company_id', $tenant->id)
            ->whereKey($this->filterData['supplier_id'])
            ->first();

        if ($supplier === null) {
            Notification::make()->danger()->title('المورد غير موجود')->send();

            return;
        }

        $this->statementReady = true;
        $this->selectedSupplierName = $supplier->name_ar;
        $this->selectedSupplierCode = str_pad((string) $supplier->getKey(), 5, '0', STR_PAD_LEFT);

        $this->refreshRunningBalances();
        $this->resetTable();
    }

    protected function refreshRunningBalances(): void
    {
        $this->runningBalances = [];

        if (! $this->statementReady || ! is_array($this->filterData)) {
            return;
        }

        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $supplierId = (int) ($this->filterData['supplier_id'] ?? 0);
        $supplier = Supplier::query()
            ->where('company_id', $tenant->id)
            ->whereKey($supplierId)
            ->first();

        if ($supplier === null) {
            return;
        }

        $from = Carbon::parse($this->filterData['date_from'])->startOfDay();
        $fromDate = $from->toDateString();

        $priorNet = (float) (SupplierLedgerEntry::query()
            ->where('company_id', $tenant->id)
            ->where('supplier_id', $supplier->id)
            ->where('entry_date', '<', $fromDate)
            ->sum(DB::raw('debit - credit')) ?: 0);

        $running = (float) $supplier->opening_balance + $priorNet;

        $entries = SupplierLedgerEntry::query()
            ->where('company_id', $tenant->id)
            ->where('supplier_id', $supplier->id)
            ->whereBetween('entry_date', [
                $fromDate,
                Carbon::parse($this->filterData['date_to'])->toDateString(),
            ])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        foreach ($entries as $entry) {
            $running += (float) $entry->debit - (float) $entry->credit;
            $this->runningBalances[$entry->getKey()] = $running;
        }
    }

    protected function getTableQuery(): Builder
    {
        if (! $this->statementReady || ! is_array($this->filterData)) {
            return SupplierLedgerEntry::query()->whereRaw('0 = 1');
        }

        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $supplierId = (int) ($this->filterData['supplier_id'] ?? 0);

        return SupplierLedgerEntry::query()
            ->where('company_id', $tenant->id)
            ->where('supplier_id', $supplierId)
            ->whereBetween('entry_date', [
                Carbon::parse($this->filterData['date_from'])->toDateString(),
                Carbon::parse($this->filterData['date_to'])->toDateString(),
            ])
            ->orderBy('entry_date')
            ->orderBy('id');
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('entry_date')
                ->label('التاريخ')
                ->date('Y/m/d')
                ->sortable(false),
            Tables\Columns\TextColumn::make('document_type')
                ->label('نوع المستند')
                ->placeholder('—')
                ->sortable(false),
            Tables\Columns\TextColumn::make('document_number')
                ->label('رقم المستند')
                ->placeholder('—')
                ->sortable(false),
            Tables\Columns\TextColumn::make('description')
                ->label('الوصف')
                ->placeholder('—')
                ->sortable(false),
            Tables\Columns\TextColumn::make('debit')
                ->label('مدين')
                ->numeric(2)
                ->sortable(false),
            Tables\Columns\TextColumn::make('credit')
                ->label('دائن')
                ->numeric(2)
                ->sortable(false),
            Tables\Columns\TextColumn::make('statement_balance')
                ->label('الرصيد')
                ->getStateUsing(function ($record): float {
                    if (! $record instanceof SupplierLedgerEntry) {
                        return 0.0;
                    }

                    return (float) ($this->runningBalances[$record->getKey()] ?? 0);
                })
                ->numeric(2)
                ->sortable(false),
        ];
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\Action::make('exportCsv')
                ->label('اصدار الى اكسل')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn (): bool => $this->statementReady)
                ->action(function (): ?StreamedResponse {
                    if (! $this->statementReady) {
                        Notification::make()->danger()->title('نفّذ البحث أولاً')->send();

                        return null;
                    }

                    return $this->exportStatementCsv();
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [];
    }

    protected function isTablePaginationEnabled(): bool
    {
        return false;
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        if (! $this->statementReady) {
            return 'استخدم البحث لعرض الكشف';
        }

        return 'لا توجد سجلات';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return null;
    }

    public function exportStatementCsv(): StreamedResponse
    {
        $this->refreshRunningBalances();

        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $supplierId = (int) ($this->filterData['supplier_id'] ?? 0);
        $supplier = Supplier::query()
            ->where('company_id', $tenant->id)
            ->whereKey($supplierId)
            ->first();

        abort_unless($supplier !== null, 404);

        $fileName = 'supplier-statement-'.$supplier->getKey().'-'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($tenant, $supplier): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'entry_date',
                'document_type',
                'document_number',
                'description',
                'debit',
                'credit',
                'balance',
            ]);

            SupplierLedgerEntry::query()
                ->where('company_id', $tenant->id)
                ->where('supplier_id', $supplier->id)
                ->whereBetween('entry_date', [
                    Carbon::parse($this->filterData['date_from'])->toDateString(),
                    Carbon::parse($this->filterData['date_to'])->toDateString(),
                ])
                ->orderBy('entry_date')
                ->orderBy('id')
                ->each(function (SupplierLedgerEntry $entry) use ($out): void {
                    fputcsv($out, [
                        $entry->entry_date?->format('Y-m-d'),
                        $entry->document_type,
                        $entry->document_number,
                        $entry->description,
                        $entry->debit,
                        $entry->credit,
                        $this->runningBalances[$entry->getKey()] ?? 0,
                    ]);
                });

            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'كشف حساب الموردين';
    }
}
