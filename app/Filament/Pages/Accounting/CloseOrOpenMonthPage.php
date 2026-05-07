<?php

namespace App\Filament\Pages\Accounting;

use App\Models\AccountingPeriod;
use App\Models\Company;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

final class CloseOrOpenMonthPage extends Page
{
    protected static ?string $slug = 'close-or-open-month-page';

    protected static string $view = 'filament.pages.accounting.close-or-open-month';

    protected static ?string $navigationGroup = 'المحاسبة';

    protected static ?string $title = 'اغلاق او فتح شهر';

    protected static ?string $navigationLabel = 'اغلاق او فتح شهر';

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?int $navigationSort = 11;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    public string $periodMonth = '';

    public string $notes = '';

    public ?string $status = null;

    public ?string $closedAt = null;

    public ?string $openedAt = null;

    public function mount(): void
    {
        $this->periodMonth = now()->startOfMonth()->toDateString();
        $this->loadPeriod();
    }

    public function loadPeriod(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $period = $this->currentPeriod($tenant);
        $this->status = $period?->status ?? AccountingPeriod::STATUS_OPEN;
        $this->closedAt = $period?->closed_at?->format('d/m/Y H:i');
        $this->openedAt = $period?->opened_at?->format('d/m/Y H:i');
        $this->notes = (string) ($period?->notes ?? $this->notes);
    }

    public function updatedPeriodMonth(): void
    {
        $this->notes = '';
        $this->loadPeriod();
    }

    public function closeMonth(): void
    {
        $this->saveStatus(AccountingPeriod::STATUS_CLOSED);

        Notification::make()
            ->title('تم إغلاق الشهر')
            ->success()
            ->send();
    }

    public function openMonth(): void
    {
        $this->saveStatus(AccountingPeriod::STATUS_OPEN);

        Notification::make()
            ->title('تم فتح الشهر')
            ->success()
            ->send();
    }

    public function periodLabel(): string
    {
        return Carbon::parse($this->periodMonth)->format('Y-m');
    }

    public function statusLabel(): string
    {
        return $this->status === AccountingPeriod::STATUS_CLOSED ? 'مغلق' : 'مفتوح';
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
        return 'اغلاق او فتح شهر';
    }

    private function saveStatus(string $status): void
    {
        $this->validate([
            'periodMonth' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $periodMonth = Carbon::parse($this->periodMonth)->startOfMonth()->toDateString();
        $userId = auth()->id();

        $values = [
            'status' => $status,
            'notes' => $this->notes !== '' ? $this->notes : null,
        ];

        if ($status === AccountingPeriod::STATUS_CLOSED) {
            $values['closed_by'] = $userId;
            $values['closed_at'] = now();
        } else {
            $values['opened_by'] = $userId;
            $values['opened_at'] = now();
        }

        AccountingPeriod::query()->updateOrCreate(
            ['company_id' => $tenant->id, 'period_month' => $periodMonth],
            $values,
        );

        $this->periodMonth = $periodMonth;
        $this->loadPeriod();
    }

    private function currentPeriod(Company $tenant): ?AccountingPeriod
    {
        return AccountingPeriod::query()
            ->where('company_id', $tenant->id)
            ->whereDate('period_month', Carbon::parse($this->periodMonth)->startOfMonth()->toDateString())
            ->first();
    }
}
