<?php

namespace App\Filament\Pages\Inventory;

use App\Filament\Pages\Concerns\InteractsWithFormActions;
use App\Models\Company;
use App\Models\Currency;
use App\Models\GoodsInwardVoucher;
use App\Models\GoodsInwardVoucherLine;
use App\Models\ServiceProduct;
use App\Models\Supplier;
use App\Models\Tax;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormInlineAction;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MaterialsEntryScreenPage extends Page
{
    use InteractsWithFormActions;

    protected static ?string $slug = 'materials-entry-screen-page';

    protected static string $view = 'filament.pages.inventory.materials-entry-screen';

    protected static ?string $navigationGroup = 'المخزون';

    protected static ?string $title = 'شاشة ادخال المواد';

    protected static ?string $navigationLabel = 'شاشة ادخال المواد';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 7;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->fillDefaults();
        $this->bootedInteractsWithFormActions();
    }

    public function form(Form $form): Form
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('voucher_number')
                            ->label('رقم الإدخال')
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\DateTimePicker::make('voucher_date')
                            ->label('تاريخ الإدخال')
                            ->required()
                            ->native(false)
                            ->seconds(false),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('تاريخ الاستحقاق')
                            ->native(false),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->label('المورد')
                            ->placeholder('اختر المورد')
                            ->required()
                            ->options(
                                Supplier::query()
                                    ->where('company_id', $tenant->id)
                                    ->orderBy('name_ar')
                                    ->pluck('name_ar', 'id')
                                    ->all()
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live(),
                        Forms\Components\Select::make('currency_id')
                            ->label('العملة')
                            ->required()
                            ->options(
                                Currency::query()
                                    ->where('company_id', $tenant->id)
                                    ->orderByDesc('is_main')
                                    ->orderBy('name_ar')
                                    ->get()
                                    ->mapWithKeys(fn (Currency $currency): array => [
                                        $currency->id => $currency->name_ar.($currency->is_main ? ' (رئيسي)' : ''),
                                    ])
                                    ->all()
                            )
                            ->searchable()
                            ->preload()
                            ->native(false),
                    ]),
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Placeholder::make('supplier_address')
                            ->label('عنوان المورد')
                            ->content(fn (Get $get): string => $this->supplierValue((int) ($get('supplier_id') ?: 0), 'address_ar')),
                        Forms\Components\Placeholder::make('supplier_phone')
                            ->label('هاتف المورد')
                            ->content(fn (Get $get): string => $this->supplierValue((int) ($get('supplier_id') ?: 0), 'phone')),
                        Forms\Components\Placeholder::make('supplier_tax')
                            ->label('الرقم الضريبي')
                            ->content(fn (Get $get): string => $this->supplierValue((int) ($get('supplier_id') ?: 0), 'sales_tax_number')),
                    ]),
                Forms\Components\Actions::make([
                    FormInlineAction::make('calculate')
                        ->label('احتساب')
                        ->icon('heroicon-o-calculator')
                        ->color('gray')
                        ->action(fn () => $this->calculateTotals(true)),
                ])->alignEnd(),
                Forms\Components\Repeater::make('lines')
                    ->label('المواد المدخلة')
                    ->schema([
                        Forms\Components\Grid::make(12)
                            ->schema([
                                Forms\Components\Select::make('service_product_id')
                                    ->label('المادة')
                                    ->required()
                                    ->options(
                                        ServiceProduct::query()
                                            ->where('company_id', $tenant->id)
                                            ->where('kind', 'product')
                                            ->orderBy('name_ar')
                                            ->pluck('name_ar', 'id')
                                            ->all()
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->live()
                                    ->columnSpan(['default' => 12, 'lg' => 4])
                                    ->afterStateUpdated(function ($state, Set $set) use ($tenant): void {
                                        if (! $state) {
                                            return;
                                        }

                                        $product = ServiceProduct::query()
                                            ->where('company_id', $tenant->id)
                                            ->where('kind', 'product')
                                            ->find((int) $state);

                                        if (! $product instanceof ServiceProduct) {
                                            return;
                                        }

                                        $set('description', $product->name_ar);
                                        $set('unit_label', 'وحدة');
                                        $set('unit_price', (float) $product->unit_cost);

                                        $rate = 0.0;
                                        if ($product->tax_id) {
                                            $tax = Tax::query()
                                                ->where('company_id', $tenant->id)
                                                ->find($product->tax_id);
                                            $rate = $tax instanceof Tax ? (float) $tax->rate : 0.0;
                                        }

                                        $set('tax_rate', $rate);
                                        $this->calculateTotals(false);
                                    }),
                                Forms\Components\TextInput::make('description')
                                    ->label('البيان')
                                    ->maxLength(500)
                                    ->columnSpan(['default' => 12, 'lg' => 4]),
                                Forms\Components\TextInput::make('unit_label')
                                    ->label('الوحدة')
                                    ->default('وحدة')
                                    ->maxLength(50)
                                    ->columnSpan(['default' => 6, 'lg' => 2]),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('الكمية')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0.0001)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn () => $this->calculateTotals(false))
                                    ->columnSpan(['default' => 6, 'lg' => 2]),
                            ]),
                        Forms\Components\Grid::make(12)
                            ->schema([
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('تكلفة الوحدة')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->minValue(0)
                                    ->suffix('د.أ')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn () => $this->calculateTotals(false))
                                    ->columnSpan(['default' => 12, 'sm' => 6, 'xl' => 2]),
                                Forms\Components\TextInput::make('amount_before_tax')
                                    ->label('قبل الضريبة')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->suffix('د.أ')
                                    ->columnSpan(['default' => 12, 'sm' => 6, 'xl' => 2]),
                                Forms\Components\TextInput::make('tax_rate')
                                    ->label('نسبة الضريبة')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->suffix('%')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn () => $this->calculateTotals(false))
                                    ->columnSpan(['default' => 6, 'xl' => 2]),
                                Forms\Components\TextInput::make('tax_amount')
                                    ->label('الضريبة')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->suffix('د.أ')
                                    ->columnSpan(['default' => 6, 'xl' => 2]),
                                Forms\Components\TextInput::make('line_total')
                                    ->label('الإجمالي')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->suffix('د.أ')
                                    ->columnSpan(['default' => 12, 'sm' => 6, 'xl' => 4]),
                            ]),
                    ])
                    ->itemNumbers()
                    ->addActionLabel('إضافة مادة +')
                    ->defaultItems(1)
                    ->reorderable(false)
                    ->collapsible(),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('subtotal_before_tax')
                            ->label('المجموع قبل الضريبة')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\TextInput::make('tax_total')
                            ->label('مجموع الضريبة')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\TextInput::make('grand_total')
                            ->label('المجموع النهائي')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\TextInput::make('total_in_words')
                            ->label('المجموع كتابة')
                            ->maxLength(500),
                    ]),
            ])
            ->columns(1)
            ->statePath('data');
    }

    public function calculateTotals(bool $notify = false): void
    {
        $lines = $this->data['lines'] ?? [];
        $subtotal = 0.0;
        $taxTotal = 0.0;

        foreach ($lines as $index => $line) {
            $line = $this->calculateLine(is_array($line) ? $line : []);
            $lines[$index] = $line;
            $subtotal += (float) $line['amount_before_tax'];
            $taxTotal += (float) $line['tax_amount'];
        }

        $this->data['lines'] = $lines;
        $this->data['subtotal_before_tax'] = round($subtotal, 2);
        $this->data['tax_total'] = round($taxTotal, 2);
        $this->data['grand_total'] = round($subtotal + $taxTotal, 2);

        if ($notify) {
            Notification::make()->success()->title('تم تحديث الاحتساب')->send();
        }
    }

    public function save(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $this->calculateTotals(false);
        $this->form->validate();
        $data = $this->form->getState();

        Supplier::query()
            ->where('company_id', $tenant->id)
            ->whereKey((int) $data['supplier_id'])
            ->firstOrFail();

        Currency::query()
            ->where('company_id', $tenant->id)
            ->whereKey((int) $data['currency_id'])
            ->firstOrFail();

        $lines = collect($data['lines'] ?? [])
            ->filter(fn (array $line): bool => filled($line['service_product_id'] ?? null))
            ->map(fn (array $line): array => $this->calculateLine($line))
            ->values();

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages([
                'data.lines' => 'أضف مادة واحدة على الأقل.',
            ]);
        }

        $subtotal = round($lines->sum(fn (array $line): float => (float) $line['amount_before_tax']), 2);
        $taxTotal = round($lines->sum(fn (array $line): float => (float) $line['tax_amount']), 2);
        $grandTotal = round($subtotal + $taxTotal, 2);

        DB::transaction(function () use ($tenant, $data, $lines, $subtotal, $taxTotal, $grandTotal): void {
            $voucher = GoodsInwardVoucher::query()->create([
                'company_id' => $tenant->id,
                'voucher_number' => (int) ($data['voucher_number'] ?? GoodsInwardVoucher::nextVoucherNumber($tenant->id)),
                'voucher_date' => $data['voucher_date'],
                'due_date' => $data['due_date'] ?? null,
                'supplier_id' => (int) $data['supplier_id'],
                'currency_id' => (int) $data['currency_id'],
                'purchase_order_id' => null,
                'subtotal_before_tax' => $subtotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'total_in_words' => $data['total_in_words'] ?? null,
                'user_id' => Auth::id(),
            ]);

            foreach ($lines as $index => $line) {
                $product = ServiceProduct::query()
                    ->where('company_id', $tenant->id)
                    ->where('kind', 'product')
                    ->whereKey((int) $line['service_product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                GoodsInwardVoucherLine::query()->create([
                    'goods_inward_voucher_id' => $voucher->id,
                    'service_product_id' => $product->id,
                    'description' => $line['description'] ?? $product->name_ar,
                    'unit_label' => $line['unit_label'] ?? 'وحدة',
                    'quantity' => (float) $line['quantity'],
                    'unit_price' => (float) $line['unit_price'],
                    'amount_before_tax' => (float) $line['amount_before_tax'],
                    'tax_rate' => (float) ($line['tax_rate'] ?? 0),
                    'tax_amount' => (float) $line['tax_amount'],
                    'line_total' => (float) $line['line_total'],
                    'sort_order' => $index,
                ]);

                $this->increaseStock($tenant, $product, (float) $line['quantity'], (float) $line['amount_before_tax']);
            }
        });

        Notification::make()->success()->title('تم إدخال المواد وتحديث المخزون')->send();

        $this->fillDefaults();
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('حفظ الإدخال')
                ->icon('heroicon-o-bookmark')
                ->submit('save'),
            Action::make('reset')
                ->label('تفريغ الشاشة')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->fillDefaults()),
            Action::make('list')
                ->label('سندات الإدخال')
                ->icon('heroicon-o-list-bullet')
                ->url(fn (): string => GoodsInwardVoucherPage::getUrl()),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'شاشة ادخال المواد';
    }

    private function fillDefaults(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $currency = Currency::query()
            ->where('company_id', $tenant->id)
            ->orderByDesc('is_main')
            ->first();

        $this->form->fill([
            'voucher_number' => GoodsInwardVoucher::nextVoucherNumber($tenant->id),
            'voucher_date' => now()->toDateTimeString(),
            'due_date' => null,
            'supplier_id' => null,
            'currency_id' => $currency?->id,
            'lines' => [
                [
                    'service_product_id' => null,
                    'description' => null,
                    'unit_label' => 'وحدة',
                    'quantity' => 1,
                    'unit_price' => 0,
                    'amount_before_tax' => 0,
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'line_total' => 0,
                ],
            ],
            'subtotal_before_tax' => 0,
            'tax_total' => 0,
            'grand_total' => 0,
            'total_in_words' => null,
        ]);
    }

    /**
     * @param array<string, mixed> $line
     * @return array<string, mixed>
     */
    private function calculateLine(array $line): array
    {
        $quantity = (float) ($line['quantity'] ?? 0);
        $unitPrice = (float) ($line['unit_price'] ?? 0);
        $taxRate = (float) ($line['tax_rate'] ?? 0);

        $beforeTax = round($quantity * $unitPrice, 2);
        $taxAmount = round($beforeTax * ($taxRate / 100), 2);

        $line['amount_before_tax'] = $beforeTax;
        $line['tax_amount'] = $taxAmount;
        $line['line_total'] = round($beforeTax + $taxAmount, 2);

        return $line;
    }

    private function increaseStock(Company $company, ServiceProduct $product, float $quantity, float $incomingCost): void
    {
        $stockBefore = (float) $product->stock_quantity;
        $newStock = $stockBefore + $quantity;
        $attrs = ['stock_quantity' => $newStock];

        if ($quantity > 0 && ($company->inventory_pricing ?? '') === 'average') {
            $denominator = $stockBefore + $quantity;
            if ($denominator > 0) {
                $attrs['unit_cost'] = round(
                    (($stockBefore * (float) $product->unit_cost) + $incomingCost) / $denominator,
                    2
                );
            }
        }

        $product->update($attrs);
    }

    private function supplierValue(int $supplierId, string $field): string
    {
        if ($supplierId <= 0) {
            return '—';
        }

        $tenant = Filament::getTenant();
        if (! $tenant instanceof Company) {
            return '—';
        }

        $supplier = Supplier::query()
            ->where('company_id', $tenant->id)
            ->find($supplierId);

        $value = $supplier?->{$field};

        return filled($value) ? (string) $value : '—';
    }
}
