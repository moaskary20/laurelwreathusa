<?php

namespace App\Filament\Pages\Reports;

use App\Models\Company;
use App\Models\Customer;
use App\Models\InvoiceLine;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class RevenueByCustomerPage extends Page
{
    protected static ?string $slug = 'revenue-by-customer-page';

    protected static string $view = 'filament.pages.reports.revenue-by-customer';

    protected static ?string $navigationGroup = 'تقارير';

    protected static ?string $title = 'الإيرادات حسب العميل';

    protected static ?string $navigationLabel = 'الإيرادات حسب العميل';

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?int $navigationSort = 6;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    /** فهرس فارغ = كل العملاء */
    public string $customerId = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $hasSearched = false;

    /** @var list<array{invoice_date: string, invoice_number: int|string, service_label: string, line_total: float, customer_name?: string}> */
    public array $reportRows = [];

    public float $totalValue = 0.0;

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

        $query = InvoiceLine::query()
            ->with([
                'invoice.customer' => fn ($q) => $q->where('company_id', $tenant->id),
                'serviceProduct' => fn ($q) => $q->where('company_id', $tenant->id),
            ])
            ->whereHas('invoice', function ($q) use ($tenant, $start, $end): void {
                $q->where('company_id', $tenant->id)
                    ->whereBetween('invoice_date', [$start, $end]);
            });

        if ($this->customerId !== '' && $this->customerId !== null && $this->selectedCustomer() instanceof Customer) {
            $query->whereHas('invoice', function ($q): void {
                $q->where('customer_id', (int) $this->customerId);
            });
        } elseif ($this->customerId !== '' && $this->customerId !== null) {
            $query->whereRaw('1 = 0');
        }

        $includeCustomerColumn = $this->customerId === '' || $this->customerId === null;

        $lines = $query->get()->sortBy(function (InvoiceLine $line): array {
            $inv = $line->invoice;

            return [
                $inv?->invoice_date?->timestamp ?? 0,
                $inv?->invoice_number ?? 0,
                $line->id,
            ];
        })->values();

        $this->reportRows = $lines->map(function (InvoiceLine $line) use ($includeCustomerColumn): array {
            $inv = $line->invoice;
            $sp = $line->serviceProduct;
            $serviceLabel = $sp !== null
                ? ($sp->name_ar.' ('.$sp->kindLabel().')')
                : ($line->description ?: '—');

            $row = [
                'invoice_date' => $inv?->invoice_date?->format('Y-m-d') ?? '',
                'invoice_number' => $inv?->invoice_number ?? '—',
                'service_label' => $serviceLabel,
                'line_total' => round((float) $line->line_total, 2),
            ];
            if ($includeCustomerColumn) {
                $row['customer_name'] = $inv?->customer?->name_ar ?? '—';
            }

            return $row;
        })->all();

        $this->totalValue = round(array_sum(array_column($this->reportRows, 'line_total')), 2);
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
        $fileName = 'revenue-by-customer-'.$this->dateFrom.'-'.$this->dateTo.'.csv';

        $includeCustomer = $this->customerId === '' || $this->customerId === null;

        return response()->streamDownload(function () use ($company, $period, $includeCustomer): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['الإيرادات حسب العميل']);
            fputcsv($handle, [$company]);
            fputcsv($handle, [$period]);
            fputcsv($handle, ['العميل: '.$this->headerCustomerLabel()]);
            fputcsv($handle, []);
            $headers = ['التاريخ', 'رقم الفاتورة', 'نوع الخدمة', 'القيمة'];
            if ($includeCustomer) {
                array_unshift($headers, 'اسم العميل');
            }
            fputcsv($handle, $headers);
            foreach ($this->reportRows as $row) {
                $line = [
                    $row['invoice_date'],
                    (string) $row['invoice_number'],
                    $row['service_label'],
                    number_format($row['line_total'], 2, '.', ''),
                ];
                if ($includeCustomer) {
                    array_unshift($line, $row['customer_name'] ?? '—');
                }
                fputcsv($handle, $line);
            }
            if ($includeCustomer) {
                fputcsv($handle, ['الإجمالي', '', '', '', number_format($this->totalValue, 2, '.', '')]);
            } else {
                fputcsv($handle, ['الإجمالي', '', '', number_format($this->totalValue, 2, '.', '')]);
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
        return 'الإيرادات حسب العميل';
    }
}
