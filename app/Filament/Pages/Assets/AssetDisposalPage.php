<?php

namespace App\Filament\Pages\Assets;

use App\Models\AssetDisposal;
use App\Models\Company;
use App\Models\FixedAsset;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormInlineAction;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class AssetDisposalPage extends Page
{
    protected static ?string $slug = 'asset-disposal-page';

    protected static string $view = 'filament.pages.assets.asset-disposal';

    protected static ?string $navigationGroup = 'الموجودات';

    protected static ?string $title = 'استبعاد';

    protected static ?string $navigationLabel = 'استبعاد';

    protected static ?string $navigationIcon = 'heroicon-o-archive-box-x-mark';

    protected static ?int $navigationSort = 3;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'disposal_type' => 'sale',
            'disposal_date' => now()->format('Y-m-d'),
            'fixed_asset_id' => null,
            'accumulated_display' => null,
            'net_book_display' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        return $form
            ->schema([
                Forms\Components\Radio::make('disposal_type')
                    ->label('نوع الاستبعاد')
                    ->options([
                        'sale' => 'بيع',
                        'scrap' => 'اتلاف',
                    ])
                    ->inline()
                    ->required(),
                Forms\Components\DatePicker::make('disposal_date')
                    ->label('التاريخ')
                    ->native(false)
                    ->required(),
                Forms\Components\Select::make('fixed_asset_id')
                    ->label('الاصل')
                    ->placeholder('اختر الأصل')
                    ->options(
                        FixedAsset::query()
                            ->where('company_id', $tenant->id)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all()
                    )
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required(),
                Forms\Components\Actions::make([
                    FormInlineAction::make('calculate')
                        ->label('احتساب +')
                        ->icon('heroicon-o-calculator')
                        ->color('gray')
                        ->action(fn () => $this->calculate()),
                ])
                    ->alignEnd(),
                Forms\Components\TextInput::make('accumulated_display')
                    ->label('الاستهلاك المتراكم حتى التاريخ')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('net_book_display')
                    ->label('القيمة الدفترية')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Actions::make([
                    FormInlineAction::make('saveDisposal')
                        ->label('تسجيل الاستبعاد')
                        ->icon('heroicon-o-bookmark')
                        ->action(fn () => $this->saveDisposal()),
                ])
                    ->alignEnd(),
            ])
            ->columns(1)
            ->statePath('data');
    }

    public function calculate(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $date = $this->data['disposal_date'] ?? null;
        $assetId = $this->data['fixed_asset_id'] ?? null;

        if ($date === null || $date === '') {
            throw ValidationException::withMessages([
                'data.disposal_date' => 'حدد التاريخ',
            ]);
        }
        if (! $assetId) {
            throw ValidationException::withMessages([
                'data.fixed_asset_id' => 'اختر الأصل',
            ]);
        }

        $asset = FixedAsset::query()
            ->where('company_id', $tenant->id)
            ->findOrFail((int) $assetId);

        $accum = $asset->accumulatedDepreciationAsOf($date);
        $net = $asset->netBookValueAsOf($date);

        $this->data['accumulated_display'] = number_format($accum, 2, '.', '');
        $this->data['net_book_display'] = number_format($net, 2, '.', '');

        Notification::make()->title('تم الاحتساب')->success()->send();
    }

    public function saveDisposal(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $date = $this->data['disposal_date'] ?? null;
        $assetId = $this->data['fixed_asset_id'] ?? null;
        $type = $this->data['disposal_type'] ?? null;

        if ($date === null || $date === '' || ! $assetId || ! $type) {
            throw ValidationException::withMessages([
                'data.fixed_asset_id' => 'أكمل الحقول واضغط احتساب أولاً',
            ]);
        }

        $asset = FixedAsset::query()
            ->where('company_id', $tenant->id)
            ->findOrFail((int) $assetId);

        $accum = $asset->accumulatedDepreciationAsOf($date);
        $net = $asset->netBookValueAsOf($date);

        AssetDisposal::query()->create([
            'company_id' => $tenant->id,
            'fixed_asset_id' => $asset->id,
            'disposal_type' => $type,
            'disposal_date' => $date,
            'historical_cost' => $asset->historical_cost,
            'accumulated_depreciation' => $accum,
            'net_book_value' => $net,
            'user_id' => Auth::id(),
        ]);

        $this->data['accumulated_display'] = number_format($accum, 2, '.', '');
        $this->data['net_book_display'] = number_format($net, 2, '.', '');

        Notification::make()->title('تم تسجيل الاستبعاد')->success()->send();
    }

    public function getTitle(): string|Htmlable
    {
        return 'إستبعاد';
    }
}
