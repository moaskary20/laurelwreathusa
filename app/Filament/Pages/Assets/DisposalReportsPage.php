<?php

namespace App\Filament\Pages\Assets;

use App\Models\AssetDisposal;
use App\Models\Company;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property Table $table
 */
final class DisposalReportsPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'disposal-reports-page';

    protected static string $view = 'filament.pages.assets.disposal-reports';

    protected static ?string $navigationGroup = 'الموجودات';

    protected static ?string $title = 'تقارير الاستبعاد';

    protected static ?string $navigationLabel = 'تقارير الاستبعاد';

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?int $navigationSort = 4;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    public ?string $reportYear = '';

    public function mount(): void
    {
        $this->reportYear = (string) now()->year;
    }

    public function searchByYear(): void
    {
        $this->resetTable();
    }

    public function printReport(): void
    {
        $this->js('window.print()');
    }

    public function exportToExcel(): StreamedResponse
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $query = AssetDisposal::query()
            ->where('company_id', $tenant->id)
            ->with(['fixedAsset', 'recordedBy'])
            ->orderByDesc('disposal_date');

        if ($this->reportYear !== null && $this->reportYear !== '' && ctype_digit($this->reportYear)) {
            $query->whereYear('disposal_date', (int) $this->reportYear);
        }

        $rows = $query->get();

        $fileName = 'asset-disposals-'.($this->reportYear ?: 'all').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, [
                'التاريخ',
                'الأصل',
                'نوع الاستبعاد',
                'الكلفة التاريخية',
                'الاستهلاك المتراكم',
                'القيمة الدفترية',
                'المستخدم',
            ]);
            foreach ($rows as $d) {
                fputcsv($handle, [
                    $d->disposal_date?->format('Y-m-d'),
                    $d->fixedAsset?->name,
                    $d->disposalTypeLabel(),
                    number_format((float) $d->historical_cost, 2, '.', ''),
                    number_format((float) $d->accumulated_depreciation, 2, '.', ''),
                    number_format((float) $d->net_book_value, 2, '.', ''),
                    $d->recordedBy?->name ?? '—',
                ]);
            }
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        return AssetDisposal::query()
            ->where('company_id', $tenant->id)
            ->with(['fixedAsset', 'recordedBy'])
            ->when(
                $this->reportYear !== null && $this->reportYear !== '' && ctype_digit($this->reportYear),
                fn (Builder $q): Builder => $q->whereYear('disposal_date', (int) $this->reportYear)
            )
            ->orderByDesc('disposal_date');
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('disposal_date')
                ->label('التاريخ')
                ->date('Y-m-d')
                ->sortable(),
            Tables\Columns\TextColumn::make('fixedAsset.name')
                ->label('الأصل')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('disposal_type')
                ->label('نوع الاستبعاد')
                ->formatStateUsing(fn (AssetDisposal $record): string => $record->disposalTypeLabel())
                ->sortable(),
            Tables\Columns\TextColumn::make('historical_cost')
                ->label('الكلفة التاريخية')
                ->numeric(decimalPlaces: 2)
                ->sortable(),
            Tables\Columns\TextColumn::make('accumulated_depreciation')
                ->label('الاستهلاك المتراكم')
                ->numeric(decimalPlaces: 2)
                ->sortable(),
            Tables\Columns\TextColumn::make('net_book_value')
                ->label('القيمة الدفترية')
                ->numeric(decimalPlaces: 2)
                ->sortable(),
            Tables\Columns\TextColumn::make('recordedBy.name')
                ->label('المستخدم')
                ->placeholder('—'),
        ];
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا توجد بيانات';
    }

    public function getTitle(): string|Htmlable
    {
        return 'تقارير الاستبعاد';
    }
}
