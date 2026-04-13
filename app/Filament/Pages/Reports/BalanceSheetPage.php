<?php

namespace App\Filament\Pages\Reports;

use App\Models\Company;
use App\Models\FixedAsset;
use App\Models\ServiceProduct;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BalanceSheetPage extends Page
{
    protected static ?string $slug = 'balance-sheet-page';

    protected static string $view = 'filament.pages.reports.balance-sheet';

    protected static ?string $navigationGroup = 'تقارير';

    protected static ?string $title = 'قائمة المركز المالي';

    protected static ?string $navigationLabel = 'قائمة المركز المالي';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 2;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $hasSearched = false;

    /** @var list<array{kind: string, label: string, balance: ?float}> */
    public array $reportRows = [];

    public function mount(): void
    {
        $this->dateFrom = now()->toDateString();
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

        $asOfDate = Carbon::parse($this->dateTo)->toDateString();

        $inventoryTotal = round((float) ServiceProduct::query()
            ->where('company_id', $tenant->id)
            ->get()
            ->sum(fn (ServiceProduct $p): float => $p->inventoryLineValue()), 2);

        $fixedNetTotal = round((float) FixedAsset::query()
            ->where('company_id', $tenant->id)
            ->get()
            ->sum(fn (FixedAsset $a): float => $a->netBookValueAsOf($asOfDate)), 2);

        $customerBalances = DB::table('customer_ledger_entries')
            ->where('company_id', $tenant->id)
            ->where('entry_date', '<=', $asOfDate)
            ->groupBy('customer_id')
            ->selectRaw('SUM(debit - credit) as balance')
            ->pluck('balance');

        $arTotal = 0.0;
        $customerAdvances = 0.0;
        foreach ($customerBalances as $b) {
            $x = round((float) $b, 2);
            if ($x > 0.0001) {
                $arTotal += $x;
            } elseif ($x < -0.0001) {
                $customerAdvances += abs($x);
            }
        }
        $arTotal = round($arTotal, 2);
        $customerAdvances = round($customerAdvances, 2);

        $supplierBalances = DB::table('supplier_ledger_entries')
            ->where('company_id', $tenant->id)
            ->where('entry_date', '<=', $asOfDate)
            ->groupBy('supplier_id')
            ->selectRaw('SUM(credit - debit) as balance')
            ->pluck('balance');

        $apTotal = 0.0;
        $supplierPrepayments = 0.0;
        foreach ($supplierBalances as $b) {
            $x = round((float) $b, 2);
            if ($x > 0.0001) {
                $apTotal += $x;
            } elseif ($x < -0.0001) {
                $supplierPrepayments += abs($x);
            }
        }
        $apTotal = round($apTotal, 2);
        $supplierPrepayments = round($supplierPrepayments, 2);

        $totalAssets = round($inventoryTotal + $fixedNetTotal + $arTotal + $supplierPrepayments, 2);
        $totalLiabilities = round($apTotal + $customerAdvances, 2);
        $equity = round($totalAssets - $totalLiabilities, 2);

        $rows = [];

        $rows[] = ['kind' => 'section', 'label' => 'الأصول', 'balance' => null];

        $rows[] = ['kind' => 'line', 'label' => 'المخزون (بالتكلفة)', 'balance' => $inventoryTotal];

        $rows[] = ['kind' => 'line', 'label' => 'الذمم المدينة (عملاء)', 'balance' => $arTotal];

        if ($supplierPrepayments > 0) {
            $rows[] = ['kind' => 'line', 'label' => 'دفعات مقدّمة للموردين', 'balance' => $supplierPrepayments];
        }

        $rows[] = ['kind' => 'line', 'label' => 'الأصول الثابتة (صافي القيمة الدفترية)', 'balance' => $fixedNetTotal];

        $rows[] = ['kind' => 'subtotal', 'label' => 'إجمالي الأصول', 'balance' => $totalAssets];

        $rows[] = ['kind' => 'section', 'label' => 'الخصوم', 'balance' => null];

        $rows[] = ['kind' => 'line', 'label' => 'الذمم الدائنة (موردين)', 'balance' => $apTotal];

        if ($customerAdvances > 0) {
            $rows[] = ['kind' => 'line', 'label' => 'دفعات مقدّمة من عملاء', 'balance' => $customerAdvances];
        }

        $rows[] = ['kind' => 'subtotal', 'label' => 'إجمالي الخصوم', 'balance' => $totalLiabilities];

        $rows[] = ['kind' => 'section', 'label' => 'حقوق الملكية', 'balance' => null];

        $rows[] = ['kind' => 'net', 'label' => 'صافي حقوق الملكية (محسوبة: الأصول − الخصوم)', 'balance' => $equity];

        $rows[] = ['kind' => 'muted', 'label' => 'ملاحظة: الأرصدة كما في نهاية يوم '.Carbon::parse($asOfDate)->format('d/m/Y').' (حقل «إلى»).', 'balance' => null];

        $this->reportRows = $rows;
        $this->hasSearched = true;
    }

    public function printReport(): void
    {
        $this->js('window.print()');
    }

    public function exportToExcel(): StreamedResponse
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        if (! $this->hasSearched) {
            $this->search();
        }

        $company = $tenant->trade_name ?: $tenant->legal_name ?: '—';
        $period = 'من '.$this->formatDateForDisplay($this->dateFrom).' إلى '.$this->formatDateForDisplay($this->dateTo);
        $fileName = 'balance-sheet-'.$this->dateFrom.'-'.$this->dateTo.'.csv';

        return response()->streamDownload(function () use ($company, $period): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['قائمة المركز المالي']);
            fputcsv($handle, [$company]);
            fputcsv($handle, [$period]);
            fputcsv($handle, []);
            fputcsv($handle, ['اسم الحساب', 'الرصيد']);
            foreach ($this->reportRows as $row) {
                if ($row['kind'] === 'section') {
                    fputcsv($handle, ['— '.$row['label'].' —', '']);

                    continue;
                }
                if ($row['kind'] === 'muted') {
                    fputcsv($handle, [$row['label'], '']);

                    continue;
                }
                $bal = $row['balance'];
                fputcsv($handle, [
                    $row['label'],
                    $bal === null ? '' : number_format((float) $bal, 2, '.', ''),
                ]);
            }
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
        return 'قائمة المركز المالي';
    }
}
