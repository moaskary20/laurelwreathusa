<?php

namespace App\Filament\Pages\Accounting;

use App\Models\Company;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AgingBalancesPage extends Page
{
    protected static ?string $slug = 'aging-balances-page';

    protected static string $view = 'filament.pages.accounting.aging-balances';

    protected static ?string $navigationGroup = 'المحاسبة';

    protected static ?string $title = 'اعمار الذمم';

    protected static ?string $navigationLabel = 'اعمار الذمم';

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?int $navigationSort = 10;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    public string $asOfDate = '';

    public string $partyType = 'customers';

    public bool $hasSearched = false;

    /** @var list<array{type: string, name: string, current: float, d30: float, d60: float, d90: float, over90: float, total: float}> */
    public array $rows = [];

    /** @var array{current: float, d30: float, d60: float, d90: float, over90: float, total: float} */
    public array $totals = ['current' => 0.0, 'd30' => 0.0, 'd60' => 0.0, 'd90' => 0.0, 'over90' => 0.0, 'total' => 0.0];

    public function mount(): void
    {
        $this->asOfDate = now()->toDateString();
    }

    public function search(): void
    {
        $this->validate([
            'asOfDate' => ['required', 'date'],
            'partyType' => ['required', 'in:customers,suppliers,both'],
        ]);

        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $asOfDate = Carbon::parse($this->asOfDate)->toDateString();
        $rows = [];

        if ($this->partyType === 'customers' || $this->partyType === 'both') {
            $rows = array_merge($rows, $this->agingRowsFor(
                table: 'customer_ledger_entries',
                partyTable: 'customers',
                partyKey: 'customer_id',
                tenantId: $tenant->id,
                asOfDate: $asOfDate,
                typeLabel: 'عميل',
                chargeColumn: 'debit',
                settlementColumn: 'credit',
            ));
        }

        if ($this->partyType === 'suppliers' || $this->partyType === 'both') {
            $rows = array_merge($rows, $this->agingRowsFor(
                table: 'supplier_ledger_entries',
                partyTable: 'suppliers',
                partyKey: 'supplier_id',
                tenantId: $tenant->id,
                asOfDate: $asOfDate,
                typeLabel: 'مورد',
                chargeColumn: 'credit',
                settlementColumn: 'debit',
            ));
        }

        $this->rows = collect($rows)->sortBy([['type', 'asc'], ['name', 'asc']])->values()->all();
        $this->totals = [
            'current' => round(array_sum(array_column($this->rows, 'current')), 2),
            'd30' => round(array_sum(array_column($this->rows, 'd30')), 2),
            'd60' => round(array_sum(array_column($this->rows, 'd60')), 2),
            'd90' => round(array_sum(array_column($this->rows, 'd90')), 2),
            'over90' => round(array_sum(array_column($this->rows, 'over90')), 2),
            'total' => round(array_sum(array_column($this->rows, 'total')), 2),
        ];
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

        $fileName = 'aging-balances-'.$this->asOfDate.'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['اعمار الذمم']);
            fputcsv($handle, [$this->companyDisplayName()]);
            fputcsv($handle, [$this->asOfLabel()]);
            fputcsv($handle, []);
            fputcsv($handle, ['النوع', 'الاسم', '0-30', '31-60', '61-90', '91-120', 'أكثر من 120', 'الإجمالي']);
            foreach ($this->rows as $row) {
                fputcsv($handle, [
                    $row['type'],
                    $row['name'],
                    number_format($row['current'], 2, '.', ''),
                    number_format($row['d30'], 2, '.', ''),
                    number_format($row['d60'], 2, '.', ''),
                    number_format($row['d90'], 2, '.', ''),
                    number_format($row['over90'], 2, '.', ''),
                    number_format($row['total'], 2, '.', ''),
                ]);
            }
            fputcsv($handle, ['الإجمالي', '', $this->totals['current'], $this->totals['d30'], $this->totals['d60'], $this->totals['d90'], $this->totals['over90'], $this->totals['total']]);
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

    public function asOfLabel(): string
    {
        return 'كما في : '.$this->formatDateForDisplay($this->asOfDate);
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
        return 'اعمار الذمم';
    }

    /**
     * @return list<array{type: string, name: string, current: float, d30: float, d60: float, d90: float, over90: float, total: float}>
     */
    private function agingRowsFor(string $table, string $partyTable, string $partyKey, int $tenantId, string $asOfDate, string $typeLabel, string $chargeColumn, string $settlementColumn): array
    {
        $entriesByParty = DB::table($table)
            ->join($partyTable, $table.'.'.$partyKey, '=', $partyTable.'.id')
            ->where($table.'.company_id', $tenantId)
            ->whereDate($table.'.entry_date', '<=', $asOfDate)
            ->orderBy($partyTable.'.name_ar')
            ->orderBy($table.'.entry_date')
            ->get([
                $table.'.'.$partyKey.' as party_id',
                $partyTable.'.name_ar as party_name',
                $table.'.entry_date',
                $table.'.'.$chargeColumn.' as charge',
                $table.'.'.$settlementColumn.' as settlement',
            ])
            ->groupBy('party_id');

        $asOf = Carbon::parse($asOfDate);
        $rows = [];

        foreach ($entriesByParty as $entries) {
            $openCharges = [];

            foreach ($entries as $entry) {
                $charge = round((float) $entry->charge, 2);
                $settlement = round((float) $entry->settlement, 2);

                if ($charge > 0) {
                    $openCharges[] = ['date' => (string) $entry->entry_date, 'amount' => $charge];
                }

                if ($settlement > 0) {
                    foreach ($openCharges as &$openCharge) {
                        if ($settlement <= 0) {
                            break;
                        }
                        $applied = min($openCharge['amount'], $settlement);
                        $openCharge['amount'] = round($openCharge['amount'] - $applied, 2);
                        $settlement = round($settlement - $applied, 2);
                    }
                    unset($openCharge);
                    $openCharges = array_values(array_filter($openCharges, fn (array $chargeRow): bool => $chargeRow['amount'] > 0.0001));
                }
            }

            $row = ['type' => $typeLabel, 'name' => (string) $entries->first()->party_name, 'current' => 0.0, 'd30' => 0.0, 'd60' => 0.0, 'd90' => 0.0, 'over90' => 0.0, 'total' => 0.0];

            foreach ($openCharges as $openCharge) {
                $days = Carbon::parse($openCharge['date'])->diffInDays($asOf);
                $bucket = match (true) {
                    $days <= 30 => 'current',
                    $days <= 60 => 'd30',
                    $days <= 90 => 'd60',
                    $days <= 120 => 'd90',
                    default => 'over90',
                };
                $row[$bucket] = round($row[$bucket] + $openCharge['amount'], 2);
                $row['total'] = round($row['total'] + $openCharge['amount'], 2);
            }

            if ($row['total'] > 0.0001) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}
