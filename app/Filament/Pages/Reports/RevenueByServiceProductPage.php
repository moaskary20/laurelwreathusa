<?php

namespace App\Filament\Pages\Reports;

use App\Models\Company;
use App\Models\InvoiceLine;
use App\Models\ServiceProduct;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class RevenueByServiceProductPage extends Page
{
    protected static ?string $slug = 'revenue-by-service-product-page';

    protected static string $view = 'filament.pages.reports.revenue-by-service-product';

    protected static ?string $navigationGroup = 'تقارير';

    protected static ?string $title = 'الإيرادات حسب نوع الخدمة والمنتج';

    protected static ?string $navigationLabel = 'الإيرادات حسب نوع الخدمة والمنتج';

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?int $navigationSort = 5;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    /** '' = الكل، service، product */
    public string $kindFilter = '';

    /** فهرس فارغ = كل الأصناف */
    public string $serviceProductId = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $hasSearched = false;

    /** @var list<array{invoice_date: string, customer_name: string, invoice_number: int|string, document_type: string, service_kind: string, line_total: float}> */
    public array $reportRows = [];

    public float $totalValue = 0.0;

    public function mount(): void
    {
        $this->dateFrom = now()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function updatedKindFilter(): void
    {
        $this->serviceProductId = '';
    }

    /**
     * @return array<int, string>
     */
    public function getServiceProductsOptionsProperty(): array
    {
        $tenant = Filament::getTenant();
        if (! $tenant instanceof Company) {
            return [];
        }

        $q = ServiceProduct::query()
            ->where('company_id', $tenant->id)
            ->orderBy('name_ar');

        if ($this->kindFilter === 'service') {
            $q->where('kind', 'service');
        } elseif ($this->kindFilter === 'product') {
            $q->where('kind', 'product');
        }

        return $q->pluck('name_ar', 'id')->all();
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

        if ($this->kindFilter === 'service' || $this->kindFilter === 'product') {
            $query->whereHas('serviceProduct', function ($q) use ($tenant): void {
                $q->where('company_id', $tenant->id)
                    ->where('kind', $this->kindFilter);
            });
        }

        if ($this->serviceProductId !== '' && $this->serviceProductId !== null && $this->selectedServiceProduct() instanceof ServiceProduct) {
            $query->where('service_product_id', (int) $this->serviceProductId);
        } elseif ($this->serviceProductId !== '' && $this->serviceProductId !== null) {
            $query->whereRaw('1 = 0');
        }

        $lines = $query->get()->sortBy(function (InvoiceLine $line): array {
            $inv = $line->invoice;

            return [
                $inv?->invoice_date?->timestamp ?? 0,
                $inv?->invoice_number ?? 0,
                $line->id,
            ];
        })->values();

        $this->reportRows = $lines->map(function (InvoiceLine $line): array {
            $inv = $line->invoice;
            $docKind = $inv?->line_kind ?? '';

            return [
                'invoice_date' => $inv?->invoice_date?->format('Y-m-d') ?? '',
                'customer_name' => $inv?->customer?->name_ar ?? '—',
                'invoice_number' => $inv?->invoice_number ?? '—',
                'document_type' => $this->documentTypeLabel((string) $docKind),
                'service_kind' => $line->serviceProduct?->kindLabel() ?? '—',
                'line_total' => round((float) $line->line_total, 2),
            ];
        })->all();

        $this->totalValue = round(array_sum(array_column($this->reportRows, 'line_total')), 2);
        $this->hasSearched = true;
    }

    protected function documentTypeLabel(string $lineKind): string
    {
        return match ($lineKind) {
            'goods' => 'سلع',
            'services' => 'خدمات',
            default => $lineKind !== '' ? $lineKind : '—',
        };
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
        $fileName = 'revenue-by-service-product-'.$this->dateFrom.'-'.$this->dateTo.'.csv';

        return response()->streamDownload(function () use ($company, $period): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['الإيرادات حسب نوع الخدمة والمنتج']);
            fputcsv($handle, [$company]);
            fputcsv($handle, [$period]);
            fputcsv($handle, []);
            fputcsv($handle, ['التاريخ', 'اسم العميل', 'رقم الفاتورة', 'نوع المستند', 'نوع الخدمة', 'القيمة']);
            foreach ($this->reportRows as $row) {
                fputcsv($handle, [
                    $row['invoice_date'],
                    $row['customer_name'],
                    (string) $row['invoice_number'],
                    $row['document_type'],
                    $row['service_kind'],
                    number_format($row['line_total'], 2, '.', ''),
                ]);
            }
            fputcsv($handle, ['الإجمالي', '', '', '', '', number_format($this->totalValue, 2, '.', '')]);
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

    public function companyDisplayName(): string
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Company
            ? (string) ($tenant->trade_name ?: $tenant->legal_name ?: '—')
            : '—';
    }

    protected function selectedServiceProduct(): ?ServiceProduct
    {
        $tenant = Filament::getTenant();
        if (! $tenant instanceof Company || $this->serviceProductId === '' || $this->serviceProductId === null) {
            return null;
        }

        return ServiceProduct::query()
            ->where('company_id', $tenant->id)
            ->find((int) $this->serviceProductId);
    }

    public function getTitle(): string|Htmlable
    {
        return 'الإيرادات حسب نوع الخدمة والمنتج';
    }
}
