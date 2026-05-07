<?php

namespace App\Filament\Pages\Accounting;

use App\Models\Company;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TrialBalancePage extends Page
{
    protected static ?string $slug = 'trial-balance-page';

    protected static string $view = 'filament.pages.accounting.trial-balance';

    protected static ?string $navigationGroup = 'المحاسبة';

    protected static ?string $title = 'ميزان المراجعة';

    protected static ?string $navigationLabel = 'ميزان المراجعة';

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?int $navigationSort = 9;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $hasSearched = false;

    /** @var list<array{code: string, name: string, debit: float, credit: float, balance_debit: float, balance_credit: float}> */
    public array $rows = [];

    public float $totalDebit = 0.0;

    public float $totalCredit = 0.0;

    public float $totalBalanceDebit = 0.0;

    public float $totalBalanceCredit = 0.0;

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function search(): void
    {
        $this->validate([
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date', 'after_or_equal:dateFrom'],
        ]);

        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $start = Carbon::parse($this->dateFrom)->toDateString();
        $end = Carbon::parse($this->dateTo)->toDateString();

        $accounts = DB::table('account_groups')
            ->where('company_id', $tenant->id)
            ->where('is_postable', true)
            ->orderBy('code')
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name_ar']);

        $movementByAccount = [];

        $journalRows = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.company_id', $tenant->id)
            ->whereBetween('journal_entries.entry_date', [$start, $end])
            ->groupBy('journal_entry_lines.account_group_id')
            ->selectRaw('journal_entry_lines.account_group_id as account_id, SUM(journal_entry_lines.debit) as debit, SUM(journal_entry_lines.credit) as credit')
            ->get();

        foreach ($journalRows as $row) {
            $this->addMovement($movementByAccount, (int) $row->account_id, (float) $row->debit, (float) $row->credit);
        }

        $customerRows = DB::table('customer_ledger_entries')
            ->join('customers', 'customer_ledger_entries.customer_id', '=', 'customers.id')
            ->where('customer_ledger_entries.company_id', $tenant->id)
            ->whereNotNull('customers.account_group_id')
            ->whereBetween('customer_ledger_entries.entry_date', [$start, $end])
            ->groupBy('customers.account_group_id')
            ->selectRaw('customers.account_group_id as account_id, SUM(customer_ledger_entries.debit) as debit, SUM(customer_ledger_entries.credit) as credit')
            ->get();

        foreach ($customerRows as $row) {
            $this->addMovement($movementByAccount, (int) $row->account_id, (float) $row->debit, (float) $row->credit);
        }

        $supplierRows = DB::table('supplier_ledger_entries')
            ->join('suppliers', 'supplier_ledger_entries.supplier_id', '=', 'suppliers.id')
            ->where('supplier_ledger_entries.company_id', $tenant->id)
            ->whereNotNull('suppliers.account_group_id')
            ->whereBetween('supplier_ledger_entries.entry_date', [$start, $end])
            ->groupBy('suppliers.account_group_id')
            ->selectRaw('suppliers.account_group_id as account_id, SUM(supplier_ledger_entries.debit) as debit, SUM(supplier_ledger_entries.credit) as credit')
            ->get();

        foreach ($supplierRows as $row) {
            $this->addMovement($movementByAccount, (int) $row->account_id, (float) $row->debit, (float) $row->credit);
        }

        $this->rows = $accounts
            ->map(function (object $account) use ($movementByAccount): array {
                $movement = $movementByAccount[(int) $account->id] ?? ['debit' => 0.0, 'credit' => 0.0];
                $debit = round((float) $movement['debit'], 2);
                $credit = round((float) $movement['credit'], 2);
                $net = round($debit - $credit, 2);

                return [
                    'code' => (string) ($account->code ?? ''),
                    'name' => (string) $account->name_ar,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance_debit' => $net > 0 ? $net : 0.0,
                    'balance_credit' => $net < 0 ? abs($net) : 0.0,
                ];
            })
            ->filter(fn (array $row): bool => $row['debit'] !== 0.0 || $row['credit'] !== 0.0 || $row['balance_debit'] !== 0.0 || $row['balance_credit'] !== 0.0)
            ->values()
            ->all();

        $this->totalDebit = round(array_sum(array_column($this->rows, 'debit')), 2);
        $this->totalCredit = round(array_sum(array_column($this->rows, 'credit')), 2);
        $this->totalBalanceDebit = round(array_sum(array_column($this->rows, 'balance_debit')), 2);
        $this->totalBalanceCredit = round(array_sum(array_column($this->rows, 'balance_credit')), 2);
        $this->hasSearched = true;
    }

    public function printReport(): void
    {
        $this->js('window.print()');
    }

    public function exportToExcel(): StreamedResponse
    {
        if (! $this->hasSearched) {
            $this->search();
        }

        $fileName = 'trial-balance-'.$this->dateFrom.'-'.$this->dateTo.'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['ميزان المراجعة']);
            fputcsv($handle, [$this->companyDisplayName()]);
            fputcsv($handle, [$this->periodLabel()]);
            fputcsv($handle, []);
            fputcsv($handle, ['رقم الحساب', 'اسم الحساب', 'مدين الحركة', 'دائن الحركة', 'رصيد مدين', 'رصيد دائن']);
            foreach ($this->rows as $row) {
                fputcsv($handle, [
                    $row['code'],
                    $row['name'],
                    number_format($row['debit'], 2, '.', ''),
                    number_format($row['credit'], 2, '.', ''),
                    number_format($row['balance_debit'], 2, '.', ''),
                    number_format($row['balance_credit'], 2, '.', ''),
                ]);
            }
            fputcsv($handle, ['الإجمالي', '', $this->totalDebit, $this->totalCredit, $this->totalBalanceDebit, $this->totalBalanceCredit]);
            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function formatDateForDisplay(string $date): string
    {
        try {
            return Carbon::parse($date)->format('d/m/Y');
        } catch (\Throwable) {
            return $date;
        }
    }

    public function periodLabel(): string
    {
        return 'الفترة من : '.$this->formatDateForDisplay($this->dateFrom).' إلى : '.$this->formatDateForDisplay($this->dateTo);
    }

    public function companyDisplayName(): string
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Company
            ? (string) ($tenant->trade_name ?: $tenant->legal_name ?: '—')
            : '—';
    }

    public function getTitle(): string|Htmlable
    {
        return 'ميزان المراجعة';
    }

    /**
     * @param  array<int, array{debit: float, credit: float}>  $movementByAccount
     */
    private function addMovement(array &$movementByAccount, int $accountId, float $debit, float $credit): void
    {
        $movementByAccount[$accountId] ??= ['debit' => 0.0, 'credit' => 0.0];
        $movementByAccount[$accountId]['debit'] += $debit;
        $movementByAccount[$accountId]['credit'] += $credit;
    }
}
