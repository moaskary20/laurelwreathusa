<?php

namespace App\Filament\Pages\Assets;

use App\Filament\Pages\Concerns\InteractsWithFormActions;
use App\Models\AssetCategory;
use App\Models\Company;
use App\Models\FixedAsset;
use App\Models\Supplier;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

final class AddAssetPage extends Page
{
    use InteractsWithFormActions;

    protected static ?string $slug = 'add-asset-page';

    protected static string $view = 'filament.pages.assets.add-asset';

    protected static ?string $navigationGroup = 'الموجودات';

    protected static ?string $title = 'اضافة';

    protected static ?string $navigationLabel = 'اضافة';

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';

    protected static ?int $navigationSort = 2;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $this->form->fill([
            'name' => '',
            'asset_category_id' => null,
            'historical_cost' => null,
            'purchase_date' => null,
            'useful_life_years' => null,
            'annual_depreciation_rate' => 0,
            'usage_start_date' => null,
            'depreciation_start_date' => null,
            'supplier_id' => null,
        ]);

        $this->bootedInteractsWithFormActions();
    }

    public function form(Form $form): Form
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('الأصل')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('asset_category_id')
                    ->label('التصنيف')
                    ->placeholder('اختيار التصنيف')
                    ->options(
                        AssetCategory::query()
                            ->where('company_id', $tenant->id)
                            ->orderBy('name_ar')
                            ->pluck('name_ar', 'id')
                            ->all()
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) use ($tenant): void {
                        if (! $state) {
                            $set('annual_depreciation_rate', 0);

                            return;
                        }
                        $cat = AssetCategory::query()
                            ->where('company_id', $tenant->id)
                            ->find((int) $state);
                        $set('annual_depreciation_rate', $cat ? (float) $cat->annual_depreciation_rate : 0);
                    }),
                Forms\Components\TextInput::make('historical_cost')
                    ->label('الكلفة التاريخية للأصل')
                    ->numeric()
                    ->required()
                    ->minValue(0),
                Forms\Components\DatePicker::make('purchase_date')
                    ->label('تاريخ الشراء')
                    ->native(false),
                Forms\Components\TextInput::make('useful_life_years')
                    ->label('العمر الانتاجي (سنوات)')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(200),
                Forms\Components\TextInput::make('annual_depreciation_rate')
                    ->label('نسبة الاستهلاك السنوي')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(true)
                    ->suffix('%')
                    ->default(0),
                Forms\Components\DatePicker::make('usage_start_date')
                    ->label('تاريخ بدء الاستخدام')
                    ->native(false),
                Forms\Components\DatePicker::make('depreciation_start_date')
                    ->label('تاريخ بدء الاستهلاك')
                    ->native(false),
                Forms\Components\Select::make('supplier_id')
                    ->label('اختيار موردين')
                    ->placeholder('—')
                    ->options(
                        Supplier::query()
                            ->where('company_id', $tenant->id)
                            ->orderBy('name_ar')
                            ->pluck('name_ar', 'id')
                            ->all()
                    )
                    ->searchable()
                    ->preload()
                    ->native(false),
            ])
            ->columns(1)
            ->statePath('data');
    }

    public function save(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $this->form->validate();
        $data = $this->form->getState();

        AssetCategory::query()
            ->where('company_id', $tenant->id)
            ->whereKey($data['asset_category_id'])
            ->firstOrFail();

        if (! empty($data['supplier_id'])) {
            Supplier::query()
                ->where('company_id', $tenant->id)
                ->whereKey($data['supplier_id'])
                ->firstOrFail();
        }

        FixedAsset::query()->create([
            'company_id' => $tenant->id,
            'name' => $data['name'],
            'asset_category_id' => $data['asset_category_id'],
            'historical_cost' => $data['historical_cost'],
            'purchase_date' => $data['purchase_date'] ?? null,
            'useful_life_years' => isset($data['useful_life_years']) ? (int) $data['useful_life_years'] : null,
            'annual_depreciation_rate' => (float) ($data['annual_depreciation_rate'] ?? 0),
            'usage_start_date' => $data['usage_start_date'] ?? null,
            'depreciation_start_date' => $data['depreciation_start_date'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'user_id' => Auth::id(),
        ]);

        Notification::make()->title('تم الحفظ')->success()->send();

        $this->form->fill([
            'name' => '',
            'asset_category_id' => null,
            'historical_cost' => null,
            'purchase_date' => null,
            'useful_life_years' => null,
            'annual_depreciation_rate' => 0,
            'usage_start_date' => null,
            'depreciation_start_date' => null,
            'supplier_id' => null,
        ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('حفظ')
                ->icon('heroicon-o-bookmark')
                ->submit('save'),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'اضافة';
    }
}
