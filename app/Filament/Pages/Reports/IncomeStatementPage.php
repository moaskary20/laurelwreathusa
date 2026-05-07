<?php

namespace App\Filament\Pages\Reports;

use App\Models\Company;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class IncomeStatementPage extends Page
{
    protected static ?string $slug = 'income-statement-page';

    protected static string $view = 'filament.pages.reports.income-statement';

    protected static ?string $navigationGroup = 'تقارير';

    protected static ?string $title = 'قائمة الدخل';

    protected static ?string $navigationLabel = 'قائمة الدخل';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 1;

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

        $start = Carbon::parse($this->dateFrom)->startOfDay();
        $end = Carbon::parse($this->dateTo)->endOfDay();

        $sales = DB::table('invoice_lines')
            ->join('invoices', 'invoice_lines.invoice_id', '=', 'invoices.id')
            ->leftJoin('service_products', function ($join) use ($tenant): void {
                $join->on('invoice_lines.service_product_id', '=', 'service_products.id')
                    ->where('service_products.company_id', '=', $tenant->id);
            })
            ->leftJoin('account_groups', function ($join) use ($tenant): void {
                $join->on('service_products.account_group_id', '=', 'account_groups.id')
                    ->where('account_groups.company_id', '=', $tenant->id);
            })
            ->where('invoices.company_id', $tenant->id)
            ->whereBetween('invoices.invoice_date', [$start, $end])
            ->groupBy(DB::raw('COALESCE(account_groups.id, 0)'))
            ->selectRaw('MAX(COALESCE(account_groups.name_ar, ?)) as account_name', ['غير مصنف'])
            ->selectRaw('SUM(invoice_lines.line_total) as total')
            ->orderByRaw('account_name')
            ->get();

        $purchases = DB::table('purchase_invoice_lines')
            ->join('purchase_invoices', 'purchase_invoice_lines.purchase_invoice_id', '=', 'purchase_invoices.id')
            ->leftJoin('service_products', function ($join) use ($tenant): void {
                $join->on('purchase_invoice_lines.service_product_id', '=', 'service_products.id')
                    ->where('service_products.company_id', '=', $tenant->id);
            })
            ->leftJoin('account_groups', function ($join) use ($tenant): void {
                $join->on('service_products.account_group_id', '=', 'account_groups.id')
                    ->where('account_groups.company_id', '=', $tenant->id);
            })
            ->where('purchase_invoices.company_id', $tenant->id)
            ->whereBetween('purchase_invoices.invoice_date', [$start, $end])
            ->groupBy(DB::raw('COALESCE(account_groups.id, 0)'))
            ->selectRaw('MAX(COALESCE(account_groups.name_ar, ?)) as account_name', ['غير مصنف'])
            ->selectRaw('SUM(purchase_invoice_lines.line_total) as total')
            ->orderByRaw('account_name')
            ->get();

        $rows = [];

        $rows[] = ['kind' => 'section', 'label' => 'الإيرادات', 'balance' => null];
        $totalSales = 0.0;
        foreach ($sales as $row) {
            $amt = round((float) $row->total, 2);
            $totalSales += $amt;
            $rows[] = ['kind' => 'line', 'label' => (string) $row->account_name, 'balance' => $amt];
        }
        if ($sales->isEmpty()) {
            $rows[] = ['kind' => 'muted', 'label' => 'لا توجد مبيعات في الفترة', 'balance' => null];
        }
        $rows[] = ['kind' => 'subtotal', 'label' => 'إجمالي الإيرادات', 'balance' => round($totalSales, 2)];

        $rows[] = ['kind' => 'section', 'label' => 'تكاليف ومشتريات', 'balance' => null];
        $totalPurchases = 0.0;
        foreach ($purchases as $row) {
            $amt = round((float) $row->total, 2);
            $totalPurchases += $amt;
            $rows[] = ['kind' => 'line', 'label' => (string) $row->account_name, 'balance' => $amt];
        }
        if ($purchases->isEmpty()) {
            $rows[] = ['kind' => 'muted', 'label' => 'لا توجد مشتريات في الفترة', 'balance' => null];
        }
        $rows[] = ['kind' => 'subtotal', 'label' => 'إجمالي التكاليف والمشتريات', 'balance' => round($totalPurchases, 2)];

        $rows[] = [
            'kind' => 'net',
            'label' => 'صافي الدخل (الإيرادات − التكاليف)',
            'balance' => round($totalSales - $totalPurchases, 2),
        ];

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
        $fileName = 'income-statement-'.$this->dateFrom.'-'.$this->dateTo.'.csv';

        return response()->streamDownload(function () use ($company, $period): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['قائمة الدخل']);
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
        return 'قائمة الدخل';
    }
}
