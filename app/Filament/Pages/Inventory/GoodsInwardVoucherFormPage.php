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

final class GoodsInwardVoucherFormPage extends Page
{
    use InteractsWithFormActions;

    protected static ?string $slug = 'goods-inward-voucher-form';

    protected static string $view = 'filament.pages.inventory.goods-inward-voucher-form';

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $editId = request()->query('id');
        if ($editId !== null && $editId !== '') {
            $voucher = GoodsInwardVoucher::query()
                ->where('company_id', $tenant->id)
                ->with(['lines'])
                ->findOrFail((int) $editId);

            $this->form->fill($this->voucherToFormData($voucher));
        } else {
            $defaultCurrency = Currency::query()
                ->where('company_id', $tenant->id)
                ->orderByDesc('is_main')
                ->first();

            $this->form->fill([
                'voucher_number' => GoodsInwardVoucher::nextVoucherNumber($tenant->id),
                'voucher_date' => now()->toDateTimeString(),
                'due_date' => null,
                'supplier_id' => null,
                'currency_id' => $defaultCurrency?->id,
                'lines' => [],
                'subtotal_before_tax' => 0,
                'tax_total' => 0,
                'grand_total' => 0,
                'total_in_words' => null,
            ]);
        }

        $this->bootedInteractsWithFormActions();
    }

    /**
     * @return array<string, mixed>
     */
    protected function voucherToFormData(GoodsInwardVoucher $voucher): array
    {
        return [
            'id' => $voucher->id,
            'voucher_number' => $voucher->voucher_number,
            'voucher_date' => $voucher->voucher_date?->toDateTimeString(),
            'due_date' => $voucher->due_date?->format('Y-m-d'),
            'supplier_id' => $voucher->supplier_id,
            'currency_id' => $voucher->currency_id,
            'subtotal_before_tax' => $voucher->subtotal_before_tax,
            'tax_total' => $voucher->tax_total,
            'grand_total' => $voucher->grand_total,
            'total_in_words' => $voucher->total_in_words,
            'lines' => $voucher->lines->map(fn (GoodsInwardVoucherLine $line, int $index): array => [
                'service_product_id' => $line->service_product_id,
                'description' => $line->description,
                'unit_label' => $line->unit_label,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'amount_before_tax' => $line->amount_before_tax,
                'tax_rate' => $line->tax_rate,
                'tax_amount' => $line->tax_amount,
                'line_total' => $line->line_total,
                'sort_order' => $line->sort_order ?: $index,
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    protected function applyLineCalculations(array $line): array
    {
        $qty = (float) ($line['quantity'] ?? 0);
        $unitPrice = (float) ($line['unit_price'] ?? 0);
        $rate = (float) ($line['tax_rate'] ?? 0);
        $before = round($qty * $unitPrice, 2);
        $taxAmt = round($before * ($rate / 100), 2);

        $line['amount_before_tax'] = $before;
        $line['tax_amount'] = $taxAmt;
        $line['line_total'] = round($before + $taxAmt, 2);

        return $line;
    }

    public function calculateTotals(bool $notify = true): void
    {
        $lines = $this->data['lines'] ?? [];
        $sub = 0.0;
        $tax = 0.0;
        foreach ($lines as $i => $line) {
            $lines[$i] = $this->applyLineCalculations(is_array($line) ? $line : []);
            $sub += (float) $lines[$i]['amount_before_tax'];
            $tax += (float) $lines[$i]['tax_amount'];
        }
        $this->data['lines'] = $lines;
        $this->data['subtotal_before_tax'] = round($sub, 2);
        $this->data['tax_total'] = round($tax, 2);
        $this->data['grand_total'] = round($sub + $tax, 2);

        if ($notify) {
            Notification::make()->title('تم تحديث الاحتساب')->success()->send();
        }
    }

    public function form(Form $form): Form
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        return $form
            ->schema([
                Forms\Components\Hidden::make('id'),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Select::make('supplier_id')
                                    ->label('اسم المورد')
                                    ->placeholder('اختر')
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
                                Forms\Components\Placeholder::make('supplier_address')
                                    ->label('العنوان')
                                    ->content(function (Get $get): string {
                                        $id = $get('supplier_id');
                                        if (! $id) {
                                            return '—';
                                        }
                                        $s = Supplier::query()->find((int) $id);

                                        return $s?->address_ar ?: '—';
                                    }),
                                Forms\Components\Placeholder::make('supplier_phone')
                                    ->label('رقم الهاتف')
                                    ->content(function (Get $get): string {
                                        $id = $get('supplier_id');
                                        if (! $id) {
                                            return '—';
                                        }
                                        $s = Supplier::query()->find((int) $id);

                                        return $s?->phone ?: '—';
                                    }),
                                Forms\Components\Placeholder::make('supplier_email')
                                    ->label('الايميل')
                                    ->content(function (Get $get): string {
                                        $id = $get('supplier_id');
                                        if (! $id) {
                                            return '—';
                                        }
                                        $s = Supplier::query()->find((int) $id);

                                        return $s?->email ?: '—';
                                    }),
                            ]),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\DateTimePicker::make('voucher_date')
                                    ->label('تاريخ الفاتورة')
                                    ->required()
                                    ->native(false)
                                    ->seconds(false),
                                Forms\Components\DatePicker::make('due_date')
                                    ->label('تاريخ الاستحقاق')
                                    ->native(false),
                                Forms\Components\TextInput::make('voucher_number')
                                    ->label('رقم الفاتورة')
                                    ->disabled()
                                    ->dehydrated(true),
                                Forms\Components\Select::make('currency_id')
                                    ->label('اختيار العملة')
                                    ->required()
                                    ->options(
                                        Currency::query()
                                            ->where('company_id', $tenant->id)
                                            ->orderByDesc('is_main')
                                            ->orderBy('name_ar')
                                            ->get()
                                            ->mapWithKeys(fn (Currency $c): array => [
                                                $c->id => $c->name_ar.($c->is_main ? ' (رئيسي)' : ''),
                                            ])
                                            ->all()
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                            ]),
                    ]),
                Forms\Components\Actions::make([
                    FormInlineAction::make('calculate')
                        ->label('احتساب')
                        ->icon('heroicon-o-calculator')
                        ->action(fn () => $this->calculateTotals(true)),
                ])
                    ->alignEnd(),
                Forms\Components\Repeater::make('lines')
                    ->label('بنود السند')
                    ->schema([
                        Forms\Components\Grid::make(12)
                            ->schema([
                                Forms\Components\Select::make('service_product_id')
                                    ->label('الصنف')
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
                                        $p = ServiceProduct::query()
                                            ->where('company_id', $tenant->id)
                                            ->find((int) $state);
                                        if ($p === null) {
                                            return;
                                        }
                                        $set('description', $p->name_ar);
                                        $set('unit_label', 'وحدة');
                                        $cost = (float) ($p->unit_cost ?? $p->sale_price ?? 0);
                                        $set('unit_price', $cost);
                                        $rate = 0.0;
                                        if ($p->tax_id) {
                                            $t = Tax::query()->find($p->tax_id);
                                            $rate = $t ? (float) $t->rate : 0.0;
                                        }
                                        $set('tax_rate', $rate);
                                        $this->calculateTotals(false);
                                    }),
                                Forms\Components\TextInput::make('description')
                                    ->label('الوصف')
                                    ->maxLength(500)
                                    ->columnSpan(['default' => 12, 'lg' => 4]),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('الكمية')
                                    ->numeric()
                                    ->default(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn () => $this->calculateTotals(false))
                                    ->columnSpan(['default' => 6, 'lg' => 2]),
                                Forms\Components\TextInput::make('unit_label')
                                    ->label('الوحدة')
                                    ->default('وحدة')
                                    ->maxLength(50)
                                    ->columnSpan(['default' => 6, 'lg' => 2]),
                            ]),
                        Forms\Components\Grid::make(12)
                            ->schema([
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('سعر الوحدة')
                                    ->suffix('د.أ')
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn () => $this->calculateTotals(false))
                                    ->columnSpan(['default' => 12, 'sm' => 6, 'xl' => 2]),
                                Forms\Components\TextInput::make('amount_before_tax')
                                    ->label('الإجمالي قبل الضريبة')
                                    ->suffix('د.أ')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->columnSpan(['default' => 12, 'sm' => 6, 'xl' => 2]),
                                Forms\Components\TextInput::make('tax_rate')
                                    ->label('نسبة الضريبة')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('%')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn () => $this->calculateTotals(false))
                                    ->columnSpan(['default' => 6, 'xl' => 2]),
                                Forms\Components\TextInput::make('tax_amount')
                                    ->label('قيمة الضريبة')
                                    ->suffix('د.أ')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->columnSpan(['default' => 6, 'xl' => 2]),
                                Forms\Components\TextInput::make('line_total')
                                    ->label('الكلفة الإجمالية')
                                    ->suffix('د.أ')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->columnSpan(['default' => 12, 'sm' => 6, 'xl' => 4]),
                            ]),
                    ])
                    ->itemNumbers()
                    ->addActionLabel('اضافه سطر +')
                    ->defaultItems(0)
                    ->reorderable(false)
                    ->collapsible(),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('subtotal_before_tax')
                            ->label('المجموع قبل الضريبة (دينار اردني)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\TextInput::make('tax_total')
                            ->label('الضريبه (دينار اردني)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\TextInput::make('grand_total')
                            ->label('المجموع النهائي (دينار اردني)')
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

    public function save(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $this->calculateTotals(false);
        $this->form->validate();
        $data = $this->form->getState();

        Supplier::query()
            ->where('company_id', $tenant->id)
            ->whereKey($data['supplier_id'])
            ->firstOrFail();

        Currency::query()
            ->where('company_id', $tenant->id)
            ->whereKey($data['currency_id'])
            ->firstOrFail();

        $lines = collect($data['lines'] ?? [])
            ->filter(fn (array $row): bool => filled($row['service_product_id'] ?? null) || filled($row['description'] ?? null))
            ->values();

        $subtotal = 0.0;
        $taxSum = 0.0;
        foreach ($lines as $line) {
            $line = $this->applyLineCalculations($line);
            $subtotal += (float) $line['amount_before_tax'];
            $taxSum += (float) $line['tax_amount'];
        }
        $grand = round($subtotal + $taxSum, 2);

        DB::transaction(function () use ($tenant, $data, $lines, $subtotal, $taxSum, $grand): void {
            $payload = [
                'company_id' => $tenant->id,
                'supplier_id' => $data['supplier_id'],
                'currency_id' => $data['currency_id'],
                'voucher_date' => $data['voucher_date'],
                'due_date' => $data['due_date'] ?? null,
                'subtotal_before_tax' => round($subtotal, 2),
                'tax_total' => round($taxSum, 2),
                'grand_total' => $grand,
                'total_in_words' => $data['total_in_words'] ?? null,
            ];

            if (! empty($data['id'])) {
                $voucher = GoodsInwardVoucher::query()
                    ->where('company_id', $tenant->id)
                    ->findOrFail((int) $data['id']);
                $voucher->update($payload);
                $voucher->lines()->delete();
                $order = $voucher;
            } else {
                $payload['voucher_number'] = (int) ($data['voucher_number'] ?? GoodsInwardVoucher::nextVoucherNumber($tenant->id));
                $payload['user_id'] = Auth::id();
                $payload['purchase_order_id'] = null;
                $order = GoodsInwardVoucher::query()->create($payload);
            }

            foreach ($lines as $index => $line) {
                $line = $this->applyLineCalculations($line);
                GoodsInwardVoucherLine::query()->create([
                    'goods_inward_voucher_id' => $order->id,
                    'service_product_id' => $line['service_product_id'] ?? null,
                    'description' => $line['description'] ?? null,
                    'unit_label' => $line['unit_label'] ?? null,
                    'quantity' => (float) ($line['quantity'] ?? 1),
                    'unit_price' => (float) ($line['unit_price'] ?? 0),
                    'amount_before_tax' => (float) $line['amount_before_tax'],
                    'tax_rate' => (float) ($line['tax_rate'] ?? 0),
                    'tax_amount' => (float) $line['tax_amount'],
                    'line_total' => (float) $line['line_total'],
                    'sort_order' => $index,
                ]);
            }
        });

        Notification::make()->title('تم الحفظ')->success()->send();

        $this->redirect(GoodsInwardVoucherPage::getUrl());
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
            Action::make('print')
                ->label('طباعه')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn () => $this->js('window.print()')),
            Action::make('back')
                ->label('العودة للقائمة الرئيسية')
                ->icon('heroicon-o-x-mark')
                ->url(fn (): string => GoodsInwardVoucherPage::getUrl()),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'سند ادخال المواد';
    }
}
