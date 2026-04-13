<?php

namespace App\Filament\Pages\Assets;

use App\Models\Company;
use App\Models\FixedAsset;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Actions as FormActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property Table $table
 */
final class FixedAssetsRegisterPage extends Page implements HasForms, Tables\Contracts\HasTable
{
    use InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'fixed-assets-register-page';

    protected static string $view = 'filament.pages.assets.fixed-assets-register';

    protected static ?string $navigationGroup = 'الموجودات';

    protected static ?string $title = 'سجل الموجودات الثابته';

    protected static ?string $navigationLabel = 'سجل الموجودات الثابته';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 1;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    /** @var array<string, mixed> */
    public array $filters = [];

    public function mount(): void
    {
        $this->filters = [
            'financial_statements_date' => now()->format('Y-m-d'),
            'previous_year_financial_statements_date' => now()->subYear()->format('Y-m-d'),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        DatePicker::make('financial_statements_date')
                            ->label('تاريخ القوائم المالية')
                            ->native(false)
                            ->required(),
                        DatePicker::make('previous_year_financial_statements_date')
                            ->label('تاريخ القوائم المالية للسنة السابقة')
                            ->native(false)
                            ->required(),
                    ]),
                FormActions::make([
                    FormAction::make('search')
                        ->label('بحث')
                        ->icon('heroicon-o-magnifying-glass')
                        ->color('gray')
                        ->action(fn () => $this->resetTable()),
                ]),
            ])
            ->statePath('filters');
    }

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        return FixedAsset::query()
            ->where('company_id', $tenant->id)
            ->with(['assetCategory']);
    }

    public function accumulatedDepreciation(FixedAsset $record): float
    {
        $asAtStr = $this->filters['financial_statements_date'] ?? null;
        if ($asAtStr === null || $asAtStr === '') {
            return 0.0;
        }

        return $record->accumulatedDepreciationAsOf($asAtStr);
    }

    public function netBookValue(FixedAsset $record): float
    {
        $asAtStr = $this->filters['financial_statements_date'] ?? null;
        if ($asAtStr === null || $asAtStr === '') {
            return (float) $record->historical_cost;
        }

        return $record->netBookValueAsOf($asAtStr);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label('الاصل')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('assetCategory.name_ar')
                ->label('مجموعة الاصل')
                ->placeholder('—')
                ->sortable(),
            Tables\Columns\TextColumn::make('purchase_display')
                ->label('تاريخ الشراء')
                ->placeholder('—')
                ->getStateUsing(function (FixedAsset $record): ?string {
                    $d = $record->purchase_date ?? $record->usage_start_date;

                    return $d?->format('Y-m-d');
                }),
            Tables\Columns\TextColumn::make('historical_cost')
                ->label('الكلفة التاريخية للاصل')
                ->numeric(decimalPlaces: 2)
                ->sortable(),
            Tables\Columns\TextColumn::make('annual_depreciation_rate')
                ->label('نسبة الاستهلاك')
                ->suffix('%')
                ->numeric(decimalPlaces: 2)
                ->sortable(),
            Tables\Columns\TextColumn::make('useful_life_years')
                ->label('العمر الانتاجي')
                ->placeholder('—')
                ->sortable(),
            Tables\Columns\TextColumn::make('accumulated_virtual')
                ->label('الاستهلاك المتراكم')
                ->numeric(decimalPlaces: 2)
                ->getStateUsing(fn (FixedAsset $record): float => $this->accumulatedDepreciation($record)),
            Tables\Columns\TextColumn::make('net_value_virtual')
                ->label('صافي القيمة')
                ->numeric(decimalPlaces: 2)
                ->getStateUsing(fn (FixedAsset $record): float => $this->netBookValue($record)),
        ];
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا توجد بيانات';
    }

    public function getTitle(): string|Htmlable
    {
        return 'سجل الموجودات الثابته';
    }
}
