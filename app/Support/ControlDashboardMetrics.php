<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * مؤشرات وتقارير ملخصة لصفحة لوحة التحكم.
 */
final class ControlDashboardMetrics
{
    /**
     * @return array{
     *     summary: array{
     *         sales_due_total: float,
     *         sales_due_count: int,
     *         purchase_due_total: float,
     *         purchase_due_count: int,
     *         revenue: float,
     *         expenses: float,
     *         net_profit: float
     *     },
     *     sales_due_invoices: list<array<string, mixed>>,
     *     purchase_due_invoices: list<array<string, mixed>>,
     *     revenue_by_customer: list<array{label: string, total: float}>,
     *     expenses_by_supplier: list<array{label: string, total: float}>,
     *     revenue_by_account: list<array{label: string, total: float}>,
     *     expenses_by_account: list<array{label: string, total: float}>,
     *     monthly_trend: array{labels: list<string>, revenue: list<float>, expenses: list<float>, profit: list<float>},
     *     charts: array<string, mixed>
     * }
     */
    public static function build(int $companyId, Carbon $from, Carbon $to): array
    {
        $asOf = $to->copy()->endOfDay();
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();

        $salesDue = self::dueSalesInvoices($companyId, $asOf);
        $purchaseDue = self::duePurchaseInvoices($companyId, $asOf);

        $revenue = self::totalRevenue($companyId, $start, $end);
        $expenses = self::totalExpenses($companyId, $start, $end);
        $netProfit = round($revenue - $expenses, 2);

        $revenueByCustomer = self::revenueByCustomer($companyId, $start, $end);
        $expensesBySupplier = self::expensesBySupplier($companyId, $start, $end);
        $revenueByAccount = self::revenueByAccount($companyId, $start, $end);
        $expensesByAccount = self::expensesByAccount($companyId, $start, $end);
        $monthlyTrend = self::monthlyTrend($companyId, $end);

        return [
            'summary' => [
                'sales_due_total' => round($salesDue->sum('outstanding'), 2),
                'sales_due_count' => $salesDue->count(),
                'purchase_due_total' => round($purchaseDue->sum('outstanding'), 2),
                'purchase_due_count' => $purchaseDue->count(),
                'revenue' => $revenue,
                'expenses' => $expenses,
                'net_profit' => $netProfit,
            ],
            'sales_due_invoices' => $salesDue->values()->all(),
            'purchase_due_invoices' => $purchaseDue->values()->all(),
            'revenue_by_customer' => $revenueByCustomer->values()->all(),
            'expenses_by_supplier' => $expensesBySupplier->values()->all(),
            'revenue_by_account' => $revenueByAccount->values()->all(),
            'expenses_by_account' => $expensesByAccount->values()->all(),
            'monthly_trend' => $monthlyTrend,
            'charts' => self::chartPayload(
                $revenue,
                $expenses,
                $netProfit,
                $salesDue,
                $purchaseDue,
            $revenueByCustomer,
            $expensesBySupplier,
            $revenueByAccount,
            $expensesByAccount,
            $monthlyTrend,
            ),
        ];
    }

    /**
     * @return Collection<int, object{outstanding: float}>
     */
    private static function dueSalesInvoices(int $companyId, Carbon $asOf): Collection
    {
        return DB::table('invoices')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->leftJoin(
                DB::raw('(SELECT invoice_id, SUM(amount) AS paid FROM receipt_voucher_lines GROUP BY invoice_id) AS receipt_payments'),
                'invoices.id',
                '=',
                'receipt_payments.invoice_id',
            )
            ->where('invoices.company_id', $companyId)
            ->whereNotNull('invoices.due_date')
            ->whereDate('invoices.due_date', '<=', $asOf->toDateString())
            ->whereRaw('(invoices.grand_total - COALESCE(receipt_payments.paid, 0)) > 0.01')
            ->orderBy('invoices.due_date')
            ->orderBy('invoices.invoice_number')
            ->get([
                'invoices.id',
                'invoices.invoice_number',
                'invoices.invoice_date',
                'invoices.due_date',
                'invoices.grand_total',
                'customers.name_ar as party_name',
                DB::raw('ROUND(invoices.grand_total - COALESCE(receipt_payments.paid, 0), 2) AS outstanding'),
                DB::raw('COALESCE(receipt_payments.paid, 0) AS paid_amount'),
            ])
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'number' => (int) $row->invoice_number,
                'party_name' => (string) $row->party_name,
                'invoice_date' => (string) $row->invoice_date,
                'due_date' => (string) $row->due_date,
                'grand_total' => round((float) $row->grand_total, 2),
                'paid_amount' => round((float) $row->paid_amount, 2),
                'outstanding' => round((float) $row->outstanding, 2),
            ]);
    }

    /**
     * @return Collection<int, object{outstanding: float}>
     */
    private static function duePurchaseInvoices(int $companyId, Carbon $asOf): Collection
    {
        return DB::table('purchase_invoices')
            ->join('suppliers', 'purchase_invoices.supplier_id', '=', 'suppliers.id')
            ->leftJoin(
                DB::raw('(SELECT purchase_invoice_id, SUM(amount) AS paid FROM payment_voucher_lines GROUP BY purchase_invoice_id) AS payment_settlements'),
                'purchase_invoices.id',
                '=',
                'payment_settlements.purchase_invoice_id',
            )
            ->where('purchase_invoices.company_id', $companyId)
            ->whereNotNull('purchase_invoices.due_date')
            ->whereDate('purchase_invoices.due_date', '<=', $asOf->toDateString())
            ->whereRaw('(purchase_invoices.grand_total - COALESCE(payment_settlements.paid, 0)) > 0.01')
            ->orderBy('purchase_invoices.due_date')
            ->orderBy('purchase_invoices.invoice_number')
            ->get([
                'purchase_invoices.id',
                'purchase_invoices.invoice_number',
                'purchase_invoices.invoice_date',
                'purchase_invoices.due_date',
                'purchase_invoices.grand_total',
                'suppliers.name_ar as party_name',
                DB::raw('ROUND(purchase_invoices.grand_total - COALESCE(payment_settlements.paid, 0), 2) AS outstanding'),
                DB::raw('COALESCE(payment_settlements.paid, 0) AS paid_amount'),
            ])
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'number' => (int) $row->invoice_number,
                'party_name' => (string) $row->party_name,
                'invoice_date' => (string) $row->invoice_date,
                'due_date' => (string) $row->due_date,
                'grand_total' => round((float) $row->grand_total, 2),
                'paid_amount' => round((float) $row->paid_amount, 2),
                'outstanding' => round((float) $row->outstanding, 2),
            ]);
    }

    private static function totalRevenue(int $companyId, Carbon $start, Carbon $end): float
    {
        return round((float) DB::table('invoice_lines')
            ->join('invoices', 'invoice_lines.invoice_id', '=', 'invoices.id')
            ->where('invoices.company_id', $companyId)
            ->whereBetween('invoices.invoice_date', [$start, $end])
            ->sum('invoice_lines.line_total'), 2);
    }

    private static function totalExpenses(int $companyId, Carbon $start, Carbon $end): float
    {
        return round((float) DB::table('purchase_invoice_lines')
            ->join('purchase_invoices', 'purchase_invoice_lines.purchase_invoice_id', '=', 'purchase_invoices.id')
            ->where('purchase_invoices.company_id', $companyId)
            ->whereBetween('purchase_invoices.invoice_date', [$start, $end])
            ->sum('purchase_invoice_lines.line_total'), 2);
    }

    /**
     * @return Collection<int, array{label: string, total: float}>
     */
    private static function revenueByCustomer(int $companyId, Carbon $start, Carbon $end): Collection
    {
        return DB::table('invoice_lines')
            ->join('invoices', 'invoice_lines.invoice_id', '=', 'invoices.id')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->where('invoices.company_id', $companyId)
            ->whereBetween('invoices.invoice_date', [$start, $end])
            ->groupBy('customers.id', 'customers.name_ar')
            ->orderByDesc(DB::raw('SUM(invoice_lines.line_total)'))
            ->limit(8)
            ->get([
                'customers.name_ar as label',
                DB::raw('ROUND(SUM(invoice_lines.line_total), 2) AS total'),
            ])
            ->map(fn (object $row): array => [
                'label' => (string) $row->label,
                'total' => (float) $row->total,
            ]);
    }

    /**
     * @return Collection<int, array{label: string, total: float}>
     */
    private static function expensesBySupplier(int $companyId, Carbon $start, Carbon $end): Collection
    {
        return DB::table('purchase_invoice_lines')
            ->join('purchase_invoices', 'purchase_invoice_lines.purchase_invoice_id', '=', 'purchase_invoices.id')
            ->join('suppliers', 'purchase_invoices.supplier_id', '=', 'suppliers.id')
            ->where('purchase_invoices.company_id', $companyId)
            ->whereBetween('purchase_invoices.invoice_date', [$start, $end])
            ->groupBy('suppliers.id', 'suppliers.name_ar')
            ->orderByDesc(DB::raw('SUM(purchase_invoice_lines.line_total)'))
            ->limit(8)
            ->get([
                'suppliers.name_ar as label',
                DB::raw('ROUND(SUM(purchase_invoice_lines.line_total), 2) AS total'),
            ])
            ->map(fn (object $row): array => [
                'label' => (string) $row->label,
                'total' => (float) $row->total,
            ]);
    }

    /**
     * @return Collection<int, array{label: string, total: float}>
     */
    private static function revenueByAccount(int $companyId, Carbon $start, Carbon $end): Collection
    {
        return DB::table('invoice_lines')
            ->join('invoices', 'invoice_lines.invoice_id', '=', 'invoices.id')
            ->leftJoin('service_products', function ($join) use ($companyId): void {
                $join->on('invoice_lines.service_product_id', '=', 'service_products.id')
                    ->where('service_products.company_id', '=', $companyId);
            })
            ->leftJoin('account_groups', function ($join) use ($companyId): void {
                $join->on('service_products.account_group_id', '=', 'account_groups.id')
                    ->where('account_groups.company_id', '=', $companyId);
            })
            ->where('invoices.company_id', $companyId)
            ->whereBetween('invoices.invoice_date', [$start, $end])
            ->groupBy(DB::raw('COALESCE(account_groups.id, 0)'))
            ->orderByDesc(DB::raw('SUM(invoice_lines.line_total)'))
            ->limit(8)
            ->get([
                DB::raw('MAX(COALESCE(account_groups.name_ar, \'غير مصنف\')) AS label'),
                DB::raw('ROUND(SUM(invoice_lines.line_total), 2) AS total'),
            ])
            ->map(fn (object $row): array => [
                'label' => (string) $row->label,
                'total' => (float) $row->total,
            ]);
    }

    /**
     * @return Collection<int, array{label: string, total: float}>
     */
    private static function expensesByAccount(int $companyId, Carbon $start, Carbon $end): Collection
    {
        return DB::table('purchase_invoice_lines')
            ->join('purchase_invoices', 'purchase_invoice_lines.purchase_invoice_id', '=', 'purchase_invoices.id')
            ->leftJoin('service_products', function ($join) use ($companyId): void {
                $join->on('purchase_invoice_lines.service_product_id', '=', 'service_products.id')
                    ->where('service_products.company_id', '=', $companyId);
            })
            ->leftJoin('account_groups', function ($join) use ($companyId): void {
                $join->on('service_products.account_group_id', '=', 'account_groups.id')
                    ->where('account_groups.company_id', '=', $companyId);
            })
            ->where('purchase_invoices.company_id', $companyId)
            ->whereBetween('purchase_invoices.invoice_date', [$start, $end])
            ->groupBy(DB::raw('COALESCE(account_groups.id, 0)'))
            ->orderByDesc(DB::raw('SUM(purchase_invoice_lines.line_total)'))
            ->limit(8)
            ->get([
                DB::raw('MAX(COALESCE(account_groups.name_ar, \'غير مصنف\')) AS label'),
                DB::raw('ROUND(SUM(purchase_invoice_lines.line_total), 2) AS total'),
            ])
            ->map(fn (object $row): array => [
                'label' => (string) $row->label,
                'total' => (float) $row->total,
            ]);
    }

    /**
     * @return array{labels: list<string>, revenue: list<float>, expenses: list<float>, profit: list<float>}
     */
    private static function monthlyTrend(int $companyId, Carbon $end): array
    {
        $labels = [];
        $revenue = [];
        $expenses = [];
        $profit = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = $end->copy()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth()->startOfDay();
            $monthEnd = $month->copy()->endOfMonth()->endOfDay();

            $labels[] = $month->format('m/Y');
            $monthRevenue = self::totalRevenue($companyId, $monthStart, $monthEnd);
            $monthExpenses = self::totalExpenses($companyId, $monthStart, $monthEnd);

            $revenue[] = $monthRevenue;
            $expenses[] = $monthExpenses;
            $profit[] = round($monthRevenue - $monthExpenses, 2);
        }

        return compact('labels', 'revenue', 'expenses', 'profit');
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $salesDue
     * @param  Collection<int, array<string, mixed>>  $purchaseDue
     * @param  Collection<int, array{label: string, total: float}>  $revenueByCustomer
     * @param  Collection<int, array{label: string, total: float}>  $expensesBySupplier
     * @param  array{labels: list<string>, revenue: list<float>, expenses: list<float>, profit: list<float>}  $monthlyTrend
     * @return array<string, mixed>
     */
    private static function chartPayload(
        float $revenue,
        float $expenses,
        float $netProfit,
        Collection $salesDue,
        Collection $purchaseDue,
        Collection $revenueByCustomer,
        Collection $expensesBySupplier,
        Collection $revenueByAccount,
        Collection $expensesByAccount,
        array $monthlyTrend,
    ): array {
        return [
            'kpi' => [
                'labels' => [
                    'فواتير مبيعات مستحقة',
                    'فواتير مشتريات مستحقة',
                    'الإيرادات',
                    'المصاريف',
                    'صافي الربح',
                ],
                'values' => [
                    round($salesDue->sum('outstanding'), 2),
                    round($purchaseDue->sum('outstanding'), 2),
                    $revenue,
                    $expenses,
                    $netProfit,
                ],
            ],
            'monthly_trend' => $monthlyTrend,
            'due_comparison' => [
                'labels' => ['مبيعات مستحقة', 'مشتريات مستحقة'],
                'values' => [
                    round($salesDue->sum('outstanding'), 2),
                    round($purchaseDue->sum('outstanding'), 2),
                ],
            ],
            'revenue_by_customer' => [
                'labels' => $revenueByCustomer->pluck('label')->all(),
                'values' => $revenueByCustomer->pluck('total')->all(),
            ],
            'expenses_by_supplier' => [
                'labels' => $expensesBySupplier->pluck('label')->all(),
                'values' => $expensesBySupplier->pluck('total')->all(),
            ],
            'revenue_by_account' => [
                'labels' => $revenueByAccount->pluck('label')->all(),
                'values' => $revenueByAccount->pluck('total')->all(),
            ],
            'expenses_by_account' => [
                'labels' => $expensesByAccount->pluck('label')->all(),
                'values' => $expensesByAccount->pluck('total')->all(),
            ],
            'profit_breakdown' => [
                'labels' => ['الإيرادات', 'المصاريف', 'صافي الربح'],
                'revenue' => $revenue,
                'expenses' => $expenses,
                'profit' => $netProfit,
            ],
        ];
    }

    public static function formatMoney(float $amount): string
    {
        return number_format($amount, 2, '.', ',');
    }

    public static function formatDate(string $date): string
    {
        try {
            return Carbon::parse($date)->format('d/m/Y');
        } catch (\Throwable) {
            return $date;
        }
    }
}
