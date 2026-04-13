<?php

namespace App\Filament\Pages\Accounting;

use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\JournalEntryLine;
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
final class AccountingAccountStatementPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'accounting-account-statement-page';

    protected static string $view = 'filament.pages.accounting.accounting-account-statement';

    protected static ?string $navigationGroup = 'المحاسبة';

    protected static ?string $title = 'كشف حساب';

    protected static ?string $navigationLabel = 'كشف حساب';

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?int $navigationSort = 8;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    /** @var array<int, float> */
    public array $runningBalances = [];

    public bool $statementReady = false;

    public ?string $selectedAccountName = null;

    public ?string $selectedAccountCode = null;

    /** @var array<string, mixed> */
    public ?array $filterData = [];

    public function mount(): void
    {
        $this->filterData = [
            'account_group_id' => null,
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('account_group_id')
                    ->label('اختر')
                    ->options(function (): array {
                        $tenant = Filament::getTenant();
                        abort_unless($tenant instanceof Company, 404);

                        return AccountGroup::indentedOptionsForCompany($tenant->id);
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
            'filterData.account_group_id' => [
                'required',
                'integer',
                Rule::exists('account_groups', 'id')->where('company_id', $tenant->id),
            ],
            'filterData.date_from' => 'required|date',
            'filterData.date_to' => 'required|date|after_or_equal:filterData.date_from',
        ], attributes: [
            'filterData.account_group_id' => 'الحساب',
            'filterData.date_from' => 'التاريخ من',
            'filterData.date_to' => 'إلى',
        ]);

        $group = AccountGroup::query()
            ->where('company_id', $tenant->id)
            ->whereKey($this->filterData['account_group_id'])
            ->first();

        if ($group === null) {
            Notification::make()->danger()->title('الحساب غير موجود')->send();

            return;
        }

        $this->statementReady = true;
        $this->selectedAccountName = $group->name_ar;
        $this->selectedAccountCode = str_pad((string) $group->getKey(), 5, '0', STR_PAD_LEFT);

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

        $accountGroupId = (int) ($this->filterData['account_group_id'] ?? 0);

        $group = AccountGroup::query()
            ->where('company_id', $tenant->id)
            ->whereKey($accountGroupId)
            ->first();

        if ($group === null) {
            return;
        }

        $from = Carbon::parse($this->filterData['date_from'])->startOfDay();
        $fromDate = $from->toDateString();

        $priorNet = (float) (JournalEntryLine::query()
            ->join('journal_entries as je', 'journal_entry_lines.journal_entry_id', '=', 'je.id')
            ->where('je.company_id', $tenant->id)
            ->where('journal_entry_lines.account_group_id', $group->id)
            ->whereDate('je.entry_date', '<', $fromDate)
            ->sum(DB::raw('journal_entry_lines.debit - journal_entry_lines.credit')) ?: 0);

        $running = $priorNet;

        $entries = JournalEntryLine::query()
            ->select('journal_entry_lines.*')
            ->join('journal_entries as je', 'journal_entry_lines.journal_entry_id', '=', 'je.id')
            ->where('je.company_id', $tenant->id)
            ->where('journal_entry_lines.account_group_id', $group->id)
            ->whereDate('je.entry_date', '>=', $fromDate)
            ->whereDate('je.entry_date', '<=', Carbon::parse($this->filterData['date_to'])->toDateString())
            ->orderBy('je.entry_date')
            ->orderBy('journal_entry_lines.id')
            ->get();

        foreach ($entries as $entry) {
            $running += (float) $entry->debit - (float) $entry->credit;
            $this->runningBalances[$entry->getKey()] = $running;
        }
    }

    protected function getTableQuery(): Builder
    {
        if (! $this->statementReady || ! is_array($this->filterData)) {
            return JournalEntryLine::query()->whereRaw('0 = 1');
        }

        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $accountGroupId = (int) ($this->filterData['account_group_id'] ?? 0);

        $fromDate = Carbon::parse($this->filterData['date_from'])->toDateString();
        $toDate = Carbon::parse($this->filterData['date_to'])->toDateString();

        return JournalEntryLine::query()
            ->select('journal_entry_lines.*')
            ->join('journal_entries as je', 'journal_entry_lines.journal_entry_id', '=', 'je.id')
            ->where('je.company_id', $tenant->id)
            ->where('journal_entry_lines.account_group_id', $accountGroupId)
            ->whereDate('je.entry_date', '>=', $fromDate)
            ->whereDate('je.entry_date', '<=', $toDate)
            ->orderBy('je.entry_date')
            ->orderBy('journal_entry_lines.id')
            ->with(['journalEntry']);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('journalEntry.entry_date')
                ->label('التاريخ')
                ->date('Y/m/d')
                ->sortable(false),
            Tables\Columns\TextColumn::make('document_type')
                ->label('نوع المستند')
                ->getStateUsing(function (JournalEntryLine $record): string {
                    $title = $record->journalEntry?->title;

                    return filled($title) ? (string) $title : 'قيد يومية';
                })
                ->sortable(false),
            Tables\Columns\TextColumn::make('journalEntry.entry_number')
                ->label('رقم المستند')
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
                    if (! $record instanceof JournalEntryLine) {
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

        $accountGroupId = (int) ($this->filterData['account_group_id'] ?? 0);
        $group = AccountGroup::query()
            ->where('company_id', $tenant->id)
            ->whereKey($accountGroupId)
            ->first();

        abort_unless($group !== null, 404);

        $fileName = 'account-statement-'.$group->getKey().'-'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($tenant, $group): void {
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

            $fromDate = Carbon::parse($this->filterData['date_from'])->toDateString();
            $toDate = Carbon::parse($this->filterData['date_to'])->toDateString();

            JournalEntryLine::query()
                ->select('journal_entry_lines.*')
                ->join('journal_entries as je', 'journal_entry_lines.journal_entry_id', '=', 'je.id')
                ->where('je.company_id', $tenant->id)
                ->where('journal_entry_lines.account_group_id', $group->id)
                ->whereDate('je.entry_date', '>=', $fromDate)
                ->whereDate('je.entry_date', '<=', $toDate)
                ->orderBy('je.entry_date')
                ->orderBy('journal_entry_lines.id')
                ->with(['journalEntry'])
                ->each(function (JournalEntryLine $line) use ($out): void {
                    $je = $line->journalEntry;
                    $docType = filled($je?->title) ? (string) $je->title : 'قيد يومية';

                    fputcsv($out, [
                        $je?->entry_date?->format('Y-m-d'),
                        $docType,
                        $je?->entry_number,
                        $line->description,
                        $line->debit,
                        $line->credit,
                        $this->runningBalances[$line->getKey()] ?? 0,
                    ]);
                });

            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'كشف حساب';
    }
}
