<?php

namespace App\Filament\Pages\Administration;

use App\Models\Company;
use App\Support\ControlDashboardMetrics;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

final class ControlDashboardPage extends Page
{
    protected static ?string $slug = 'control-dashboard-page';

    protected static string $view = 'filament.pages.administration.control-dashboard';

    protected static ?string $navigationGroup = 'إدارة';

    protected static ?string $title = 'لوحة التحكم';

    protected static ?string $navigationLabel = 'لوحة التحكم';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?int $navigationSort = 0;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $hasLoaded = false;

    /** @var array<string, mixed> */
    public array $metrics = [];

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->loadDashboard();
    }

    public function loadDashboard(): void
    {
        $this->validate([
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date', 'after_or_equal:dateFrom'],
        ]);

        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $this->metrics = ControlDashboardMetrics::build(
            (int) $tenant->id,
            Carbon::parse($this->dateFrom)->startOfDay(),
            Carbon::parse($this->dateTo)->endOfDay(),
        );

        $this->hasLoaded = true;
        $this->dispatch('control-dashboard-charts-updated', charts: $this->metrics['charts'] ?? []);
    }

    public function periodLabel(): string
    {
        return 'من '.ControlDashboardMetrics::formatDate($this->dateFrom)
            .' إلى '.ControlDashboardMetrics::formatDate($this->dateTo);
    }

    public function companyDisplayName(): string
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Company
            ? (string) ($tenant->trade_name ?: $tenant->legal_name ?: '—')
            : '—';
    }

    /**
     * @return array<string, float|int>
     */
    public function summary(): array
    {
        /** @var array<string, float|int> $summary */
        $summary = $this->metrics['summary'] ?? [];

        return $summary;
    }

    public function getTitle(): string|Htmlable
    {
        return 'لوحة التحكم';
    }
}
