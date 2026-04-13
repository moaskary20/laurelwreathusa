<?php

namespace App\Filament\Pages\Reports;

use App\Models\Company;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class VatSuppliersReportPage extends Page
{
    protected static ?string $slug = 'vat-suppliers-report-page';

    protected static string $view = 'filament.pages.reports.vat-suppliers-report';

    protected static ?string $navigationGroup = 'تقارير';

    protected static ?string $title = 'ضريبة القيمة المضافة للموردين';

    protected static ?string $navigationLabel = 'ضريبة القيمة المضافة للموردين';

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?int $navigationSort = 4;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    /** فهرس فارغ = كل الموردين */
    public string $supplierId = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $hasSearched = false;

    /** @var list<array{invoice_date: string, invoice_number: int|string, before_tax: float, tax: float, after_tax: float, supplier_name?: string}> */
    public array $reportRows = [];

    public function mount(): void
    {
        $this->dateFrom = now()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    /**
     * @return array<int, string>
     */
    public function getSuppliersOptionsProperty(): array
    {
        $tenant = Filament::getTenant();
        if (! $tenant instanceof Company) {
            return [];
        }

        return Supplier::query()
            ->where('company_id', $tenant->id)
            ->orderBy('name_ar')
            ->pluck('name_ar', 'id')
            ->all();
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

        $query = PurchaseInvoice::query()
            ->where('company_id', $tenant->id)
            ->whereBetween('invoice_date', [$start, $end])
            ->with('supplier')
            ->orderBy('invoice_date')
            ->orderBy('invoice_number');

        if ($this->supplierId !== '' && $this->supplierId !== null) {
            $query->where('supplier_id', (int) $this->supplierId);
        }

        $includeSupplierColumn = $this->supplierId === '' || $this->supplierId === null;

        $this->reportRows = $query->get()->map(function (PurchaseInvoice $inv) use ($includeSupplierColumn): array {
            $row = [
                'invoice_date' => $inv->invoice_date?->format('Y-m-d') ?? '',
                'invoice_number' => $inv->invoice_number,
                'before_tax' => round((float) $inv->total_after_discount, 2),
                'tax' => round((float) $inv->tax_amount, 2),
                'after_tax' => round((float) $inv->grand_total, 2),
            ];
            if ($includeSupplierColumn) {
                $row['supplier_name'] = $inv->supplier?->name_ar ?? '—';
            }

            return $row;
        })->all();

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
        $fileName = 'vat-suppliers-'.$this->dateFrom.'-'.$this->dateTo.'.csv';

        $includeSupplier = $this->supplierId === '' || $this->supplierId === null;

        return response()->streamDownload(function () use ($company, $period, $includeSupplier): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['ضريبة القيمة المضافة للموردين']);
            fputcsv($handle, [$company]);
            fputcsv($handle, [$period]);
            fputcsv($handle, ['اسم المورد: '.$this->headerSupplierLabel()]);
            fputcsv($handle, ['الرقم الضريبي: '.$this->headerTaxNumberLabel()]);
            fputcsv($handle, []);
            $headers = ['التاريخ', 'رقم الفاتورة', 'المبلغ قبل الضريبة', 'الضريبة', 'المبلغ بعد الضريبة'];
            if ($includeSupplier) {
                array_unshift($headers, 'المورد');
            }
            fputcsv($handle, $headers);
            foreach ($this->reportRows as $row) {
                $line = [
                    $row['invoice_date'],
                    (string) $row['invoice_number'],
                    number_format($row['before_tax'], 2, '.', ''),
                    number_format($row['tax'], 2, '.', ''),
                    number_format($row['after_tax'], 2, '.', ''),
                ];
                if ($includeSupplier) {
                    array_unshift($line, $row['supplier_name'] ?? '—');
                }
                fputcsv($handle, $line);
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

    public function headerSupplierLabel(): string
    {
        if ($this->supplierId === '' || $this->supplierId === null) {
            return 'جميع الموردين';
        }
        $s = Supplier::query()->find((int) $this->supplierId);

        return $s?->name_ar ?? '—';
    }

    public function headerTaxNumberLabel(): string
    {
        if ($this->supplierId === '' || $this->supplierId === null) {
            return '—';
        }
        $s = Supplier::query()->find((int) $this->supplierId);

        return filled($s?->sales_tax_number) ? (string) $s->sales_tax_number : '—';
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
        return 'ضريبة القيمة المضافة للموردين';
    }
}
