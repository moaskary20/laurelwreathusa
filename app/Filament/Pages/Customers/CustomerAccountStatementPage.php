<?php

namespace App\Filament\Pages\Customers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
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
final class CustomerAccountStatementPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'customer-account-statement-page';

    protected static string $view = 'filament.pages.customers.customer-account-statement';

    protected static ?string $navigationGroup = 'العملاء';

    protected static ?string $title = 'كشف حساب عملاء';

    protected static ?string $navigationLabel = 'كشف حساب عملاء';

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?int $navigationSort = 2;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    /** @var array<int, float> */
    public array $runningBalances = [];

    public bool $statementReady = false;

    public ?string $selectedCustomerName = null;

    public ?string $selectedCustomerCode = null;

    /** @var array<string, mixed> */
    public ?array $filterData = [];

    public function mount(): void
    {
        $this->filterData = [
            'customer_id' => null,
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->label('اختيار عميل')
                    ->options(function (): array {
                        $tenant = Filament::getTenant();
                        abort_unless($tenant instanceof Company, 404);

                        return Customer::query()
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
            'filterData.customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where('company_id', $tenant->id),
            ],
            'filterData.date_from' => 'required|date',
            'filterData.date_to' => 'required|date|after_or_equal:filterData.date_from',
        ], attributes: [
            'filterData.customer_id' => 'العميل',
            'filterData.date_from' => 'التاريخ من',
            'filterData.date_to' => 'إلى',
        ]);

        $customer = Customer::query()
            ->where('company_id', $tenant->id)
            ->whereKey($this->filterData['customer_id'])
            ->first();

        if ($customer === null) {
            Notification::make()->danger()->title('العميل غير موجود')->send();

            return;
        }

        $this->statementReady = true;
        $this->selectedCustomerName = $customer->name_ar;
        $this->selectedCustomerCode = str_pad((string) $customer->getKey(), 5, '0', STR_PAD_LEFT);

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

        $customerId = (int) ($this->filterData['customer_id'] ?? 0);
        $customer = Customer::query()
            ->where('company_id', $tenant->id)
            ->whereKey($customerId)
            ->first();

        if ($customer === null) {
            return;
        }

        $from = Carbon::parse($this->filterData['date_from'])->startOfDay();
        $fromDate = $from->toDateString();

        $priorNet = (float) (CustomerLedgerEntry::query()
            ->where('company_id', $tenant->id)
            ->where('customer_id', $customer->id)
            ->where('entry_date', '<', $fromDate)
            ->sum(DB::raw('debit - credit')) ?: 0);

        $running = (float) $customer->opening_balance + $priorNet;

        $entries = CustomerLedgerEntry::query()
            ->where('company_id', $tenant->id)
            ->where('customer_id', $customer->id)
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
            return CustomerLedgerEntry::query()->whereRaw('0 = 1');
        }

        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $customerId = (int) ($this->filterData['customer_id'] ?? 0);

        return CustomerLedgerEntry::query()
            ->where('company_id', $tenant->id)
            ->where('customer_id', $customerId)
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
                    if (! $record instanceof CustomerLedgerEntry) {
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
                ->label('إصدار إلى إكسل')
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

        $customerId = (int) ($this->filterData['customer_id'] ?? 0);
        $customer = Customer::query()
            ->where('company_id', $tenant->id)
            ->whereKey($customerId)
            ->first();

        abort_unless($customer !== null, 404);

        $fileName = 'customer-statement-'.$customer->getKey().'-'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($tenant, $customer): void {
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

            CustomerLedgerEntry::query()
                ->where('company_id', $tenant->id)
                ->where('customer_id', $customer->id)
                ->whereBetween('entry_date', [
                    Carbon::parse($this->filterData['date_from'])->toDateString(),
                    Carbon::parse($this->filterData['date_to'])->toDateString(),
                ])
                ->orderBy('entry_date')
                ->orderBy('id')
                ->each(function (CustomerLedgerEntry $entry) use ($out): void {
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
        return 'كشف حساب عملاء';
    }
}
