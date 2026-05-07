<?php

namespace App\Filament\Pages\Accounting;

use App\Models\Company;
use App\Models\FiscalYearClosing;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

final class FiscalYearClosingPage extends Page
{
    protected static ?string $slug = 'fiscal-year-closing-page';

    protected static string $view = 'filament.pages.accounting.fiscal-year-closing';

    protected static ?string $navigationGroup = 'المحاسبة';

    protected static ?string $title = 'إغلاق السنة المالية';

    protected static ?string $navigationLabel = 'إغلاق السنة المالية';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 12;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    public string $yearStart = '';

    public string $yearEnd = '';

    public string $notes = '';

    public ?string $status = null;

    public ?string $closedAt = null;

    public ?string $openedAt = null;

    public function mount(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $end = $tenant->fiscal_year_end instanceof Carbon
            ? $tenant->fiscal_year_end->copy()
            : now()->endOfYear();

        if ($end->isPast() && ! $end->isSameYear(now())) {
            $end = now()->endOfYear();
        }

        $this->yearEnd = $end->toDateString();
        $this->yearStart = $end->copy()->subYear()->addDay()->toDateString();
        $this->loadClosing();
    }

    public function loadClosing(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $closing = $this->currentClosing($tenant);
        $this->status = $closing?->status ?? FiscalYearClosing::STATUS_OPEN;
        $this->closedAt = $closing?->closed_at?->format('d/m/Y H:i');
        $this->openedAt = $closing?->opened_at?->format('d/m/Y H:i');
        $this->notes = (string) ($closing?->notes ?? $this->notes);
    }

    public function updatedYearStart(): void
    {
        $this->notes = '';
        $this->loadClosing();
    }

    public function updatedYearEnd(): void
    {
        $this->notes = '';
        $this->loadClosing();
    }

    public function closeYear(): void
    {
        $this->saveStatus(FiscalYearClosing::STATUS_CLOSED);

        Notification::make()
            ->title('تم إغلاق السنة المالية')
            ->success()
            ->send();
    }

    public function openYear(): void
    {
        $this->saveStatus(FiscalYearClosing::STATUS_OPEN);

        Notification::make()
            ->title('تم فتح السنة المالية')
            ->success()
            ->send();
    }

    public function statusLabel(): string
    {
        return $this->status === FiscalYearClosing::STATUS_CLOSED ? 'مغلقة' : 'مفتوحة';
    }

    public function periodLabel(): string
    {
        return 'من '.$this->formatDateForDisplay($this->yearStart).' إلى '.$this->formatDateForDisplay($this->yearEnd);
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

    public function getTitle(): string|Htmlable
    {
        return 'إغلاق السنة المالية';
    }

    private function saveStatus(string $status): void
    {
        $this->validate([
            'yearStart' => ['required', 'date'],
            'yearEnd' => ['required', 'date', 'after_or_equal:yearStart'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $userId = auth()->id();
        $values = [
            'status' => $status,
            'notes' => $this->notes !== '' ? $this->notes : null,
        ];

        if ($status === FiscalYearClosing::STATUS_CLOSED) {
            $values['closed_by'] = $userId;
            $values['closed_at'] = now();
        } else {
            $values['opened_by'] = $userId;
            $values['opened_at'] = now();
        }

        FiscalYearClosing::query()->updateOrCreate(
            [
                'company_id' => $tenant->id,
                'year_start' => Carbon::parse($this->yearStart)->toDateString(),
                'year_end' => Carbon::parse($this->yearEnd)->toDateString(),
            ],
            $values,
        );

        $this->loadClosing();
    }

    private function currentClosing(Company $tenant): ?FiscalYearClosing
    {
        return FiscalYearClosing::query()
            ->where('company_id', $tenant->id)
            ->whereDate('year_start', Carbon::parse($this->yearStart)->toDateString())
            ->whereDate('year_end', Carbon::parse($this->yearEnd)->toDateString())
            ->first();
    }
}
