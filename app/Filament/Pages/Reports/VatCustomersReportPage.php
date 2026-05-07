<?php

namespace App\Filament\Pages\Reports;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class VatCustomersReportPage extends Page
{
    protected static ?string $slug = 'vat-customers-report-page';

    protected static string $view = 'filament.pages.reports.vat-customers-report';

    protected static ?string $navigationGroup = 'تقارير';

    protected static ?string $title = 'ضريبة القيمة المضافة للعملاء';

    protected static ?string $navigationLabel = 'ضريبة القيمة المضافة للعملاء';

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?int $navigationSort = 3;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    /** فهرس فارغ = كل العملاء */
    public string $customerId = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $hasSearched = false;

    /** @var list<array{invoice_date: string, invoice_number: int|string, before_tax: float, tax: float, after_tax: float, customer_name?: string}> */
    public array $reportRows = [];

    public function mount(): void
    {
        $this->dateFrom = now()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    /**
     * @return array<int, string>
     */
    public function getCustomersOptionsProperty(): array
    {
        $tenant = Filament::getTenant();
        if (! $tenant instanceof Company) {
            return [];
        }

        return Customer::query()
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

        $query = Invoice::query()
            ->where('company_id', $tenant->id)
            ->whereBetween('invoice_date', [$start, $end])
            ->with(['customer' => fn ($q) => $q->where('company_id', $tenant->id)])
            ->orderBy('invoice_date')
            ->orderBy('invoice_number');

        if ($this->customerId !== '' && $this->customerId !== null && $this->selectedCustomer() instanceof Customer) {
            $query->where('customer_id', (int) $this->customerId);
        } elseif ($this->customerId !== '' && $this->customerId !== null) {
            $query->whereRaw('1 = 0');
        }

        $includeCustomerColumn = $this->customerId === '' || $this->customerId === null;

        $this->reportRows = $query->get()->map(function (Invoice $inv) use ($includeCustomerColumn): array {
            $row = [
                'invoice_date' => $inv->invoice_date?->format('Y-m-d') ?? '',
                'invoice_number' => $inv->invoice_number,
                'before_tax' => round((float) $inv->total_after_discount, 2),
                'tax' => round((float) $inv->tax_amount, 2),
                'after_tax' => round((float) $inv->grand_total, 2),
            ];
            if ($includeCustomerColumn) {
                $row['customer_name'] = $inv->customer?->name_ar ?? '—';
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
        $fileName = 'vat-customers-'.$this->dateFrom.'-'.$this->dateTo.'.csv';

        $includeCustomer = $this->customerId === '' || $this->customerId === null;

        return response()->streamDownload(function () use ($company, $period, $includeCustomer): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['ضريبة القيمة المضافة للعملاء']);
            fputcsv($handle, [$company]);
            fputcsv($handle, [$period]);
            fputcsv($handle, ['اسم العميل: '.$this->headerCustomerLabel()]);
            fputcsv($handle, ['الرقم الضريبي: '.$this->headerTaxNumberLabel()]);
            fputcsv($handle, []);
            $headers = ['التاريخ', 'رقم الفاتورة', 'المبلغ قبل الضريبة', 'الضريبة', 'المبلغ بعد الضريبة'];
            if ($includeCustomer) {
                array_unshift($headers, 'العميل');
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
                if ($includeCustomer) {
                    array_unshift($line, $row['customer_name'] ?? '—');
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

    public function headerCustomerLabel(): string
    {
        if ($this->customerId === '' || $this->customerId === null) {
            return 'جميع العملاء';
        }
        $c = $this->selectedCustomer();

        return $c?->name_ar ?? '—';
    }

    public function headerTaxNumberLabel(): string
    {
        if ($this->customerId === '' || $this->customerId === null) {
            return '—';
        }
        $c = $this->selectedCustomer();

        return filled($c?->sales_tax_number) ? (string) $c->sales_tax_number : '—';
    }

    protected function selectedCustomer(): ?Customer
    {
        $tenant = Filament::getTenant();
        if (! $tenant instanceof Company || $this->customerId === '' || $this->customerId === null) {
            return null;
        }

        return Customer::query()
            ->where('company_id', $tenant->id)
            ->find((int) $this->customerId);
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
        return 'ضريبة القيمة المضافة للعملاء';
    }
}
