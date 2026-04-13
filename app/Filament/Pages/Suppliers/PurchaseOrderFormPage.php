<?php

namespace App\Filament\Pages\Suppliers;

use App\Filament\Pages\Concerns\InteractsWithFormActions;
use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Currency;
use App\Models\InvoiceText;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\ServiceProduct;
use App\Models\Supplier;
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
use Illuminate\Support\Str;

final class PurchaseOrderFormPage extends Page
{
    use InteractsWithFormActions;

    protected static ?string $slug = 'purchase-order-form';

    protected static string $view = 'filament.pages.suppliers.purchase-order-form';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'امر الشراء';

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
            $order = PurchaseOrder::query()
                ->where('company_id', $tenant->id)
                ->with(['lines', 'supplier'])
                ->findOrFail((int) $editId);

            $this->form->fill($this->purchaseOrderToFormData($order));
        } else {
            $this->form->fill([
                'order_number' => PurchaseOrder::nextOrderNumber($tenant->id),
                'order_date' => now()->toDateTimeString(),
                'due_date' => now()->toDateString(),
                'line_kind' => 'services',
                'lines' => [],
                'total_value' => 0,
            ]);
        }

        $this->bootedInteractsWithFormActions();
    }

    /**
     * @return array<string, mixed>
     */
    protected function purchaseOrderToFormData(PurchaseOrder $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'supplier_invoice_number' => $order->supplier_invoice_number,
            'supplier_id' => $order->supplier_id,
            'currency_id' => $order->currency_id,
            'order_date' => $order->order_date?->toDateTimeString(),
            'due_date' => $order->due_date?->format('Y-m-d'),
            'line_kind' => $order->line_kind,
            'total_value' => $order->total_value,
            'bank_account_id' => $order->bank_account_id,
            'invoice_text_id' => $order->invoice_text_id,
            'notes' => $order->notes,
            'lines' => $order->lines->map(fn (PurchaseOrderLine $line, int $index): array => [
                'service_product_id' => $line->service_product_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'line_total' => $line->line_total,
                'sort_order' => $line->sort_order ?: $index,
            ])->values()->all(),
        ];
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
                        Forms\Components\Select::make('supplier_id')
                            ->label('اسم المورد')
                            ->placeholder('اختيار مورد')
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
                            ->label('اختيار العملة')
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
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->label('رقم امر الشراء')
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\TextInput::make('supplier_invoice_number')
                            ->label('رقم فاتورة المورد')
                            ->maxLength(100),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\DateTimePicker::make('order_date')
                            ->label('تاريخ امر الشراء')
                            ->required()
                            ->native(false)
                            ->seconds(false),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('تاريخ الاستحقاق')
                            ->required()
                            ->native(false),
                    ]),
                Forms\Components\Radio::make('line_kind')
                    ->label('نوع البنود')
                    ->options([
                        'goods' => 'سلع',
                        'services' => 'خدمات',
                    ])
                    ->inline()
                    ->live()
                    ->default('services'),
                Forms\Components\Actions::make([
                    FormInlineAction::make('searchStub')
                        ->label('بحث')
                        ->icon('heroicon-m-magnifying-glass')
                        ->color('gray')
                        ->disabled()
                        ->tooltip('سيتم ربطه بالكتالوج لاحقاً'),
                    FormInlineAction::make('calculate')
                        ->label('احتساب')
                        ->action(fn () => $this->calculateTotals()),
                ]),
                Forms\Components\Repeater::make('lines')
                    ->label('بنود امر الشراء')
                    ->schema([
                        Forms\Components\Select::make('service_product_id')
                            ->label('الصنف')
                            ->options(function () use ($tenant): array {
                                $kind = (($this->data['line_kind'] ?? 'services') === 'goods') ? 'product' : 'service';

                                return ServiceProduct::query()
                                    ->where('company_id', $tenant->id)
                                    ->where('kind', $kind)
                                    ->orderBy('name_ar')
                                    ->pluck('name_ar', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                if (! $state) {
                                    return;
                                }
                                $p = ServiceProduct::query()->find((int) $state);
                                if ($p === null) {
                                    return;
                                }
                                $set('unit_price', $p->sale_price);
                                $set('description', $p->name_ar);
                                $qty = (float) ($get('quantity') ?: 1);
                                $set('line_total', round($qty * (float) $p->sale_price, 2));
                            }),
                        Forms\Components\TextInput::make('description')
                            ->label('الوصف')
                            ->maxLength(500),
                        Forms\Components\TextInput::make('quantity')
                            ->label('الكمية')
                            ->numeric()
                            ->default(1)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                $q = (float) ($get('quantity') ?: 0);
                                $p = (float) ($get('unit_price') ?: 0);
                                $set('line_total', round($q * $p, 2));
                            }),
                        Forms\Components\TextInput::make('unit_price')
                            ->label('سعر الوحدة')
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                $q = (float) ($get('quantity') ?: 0);
                                $p = (float) ($get('unit_price') ?: 0);
                                $set('line_total', round($q * $p, 2));
                            }),
                        Forms\Components\TextInput::make('line_total')
                            ->label('الإجمالي')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(true),
                    ])
                    ->columns(5)
                    ->addActionLabel('اضافه سطر +')
                    ->defaultItems(0)
                    ->collapsible(),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('bank_account_id')
                            ->label('اسم البنك')
                            ->options(
                                BankAccount::query()
                                    ->where('company_id', $tenant->id)
                                    ->orderBy('name_ar')
                                    ->pluck('name_ar', 'id')
                                    ->all()
                            )
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Forms\Components\Placeholder::make('bank_branch')
                            ->label('الفرع')
                            ->content(function (Get $get): string {
                                $id = $get('bank_account_id');
                                if (! $id) {
                                    return '—';
                                }
                                $b = BankAccount::query()->find((int) $id);

                                return $b?->branch_ar ?? '—';
                            }),
                        Forms\Components\Placeholder::make('bank_beneficiary')
                            ->label('اسم المستفيد من الحساب')
                            ->content(function (Get $get): string {
                                $id = $get('bank_account_id');
                                if (! $id) {
                                    return '—';
                                }
                                $b = BankAccount::query()->find((int) $id);

                                return $b?->nickname ?? '—';
                            }),
                        Forms\Components\Placeholder::make('bank_swift')
                            ->label('سويفت كود')
                            ->content(function (Get $get): string {
                                $id = $get('bank_account_id');
                                if (! $id) {
                                    return '—';
                                }
                                $b = BankAccount::query()->find((int) $id);

                                return $b?->swift_code ?? '—';
                            }),
                        Forms\Components\Placeholder::make('bank_iban')
                            ->label('IBAN')
                            ->content(function (Get $get): string {
                                $id = $get('bank_account_id');
                                if (! $id) {
                                    return '—';
                                }
                                $b = BankAccount::query()->find((int) $id);

                                return $b?->iban ?? '—';
                            }),
                        Forms\Components\Placeholder::make('bank_account_no')
                            ->label('رقم الحساب')
                            ->content(function (Get $get): string {
                                $id = $get('bank_account_id');
                                if (! $id) {
                                    return '—';
                                }
                                $b = BankAccount::query()->find((int) $id);

                                return $b?->account_number ?? '—';
                            }),
                    ]),
                Forms\Components\Select::make('invoice_text_id')
                    ->label('اضافة نص')
                    ->options(
                        InvoiceText::query()
                            ->where('company_id', $tenant->id)
                            ->orderBy('title')
                            ->get()
                            ->mapWithKeys(fn (InvoiceText $t): array => [
                                $t->id => $t->title ?: Str::limit($t->text_ar ?? '', 40),
                            ])
                            ->all()
                    )
                    ->searchable()
                    ->preload()
                    ->native(false),
                Forms\Components\Textarea::make('notes')
                    ->label('النص')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('total_value')
                    ->label('قيمة امر الشراء')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(true),
            ])
            ->columns(1)
            ->statePath('data');
    }

    public function calculateTotals(): void
    {
        $lines = $this->data['lines'] ?? [];
        $total = 0.0;
        foreach ($lines as $i => $line) {
            $qty = (float) ($line['quantity'] ?? 0);
            $price = (float) ($line['unit_price'] ?? 0);
            $lineTotal = round($qty * $price, 2);
            $lines[$i]['line_total'] = $lineTotal;
            $total += $lineTotal;
        }
        $this->data['lines'] = $lines;
        $this->data['total_value'] = round($total, 2);

        Notification::make()->title('تم تحديث الاحتساب')->success()->send();
    }

    public function save(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $this->form->validate();
        $data = $this->form->getState();
        $supplier = Supplier::query()
            ->where('company_id', $tenant->id)
            ->whereKey($data['supplier_id'])
            ->firstOrFail();

        $lines = collect($data['lines'] ?? [])
            ->filter(fn (array $row): bool => filled($row['description'] ?? null) || filled($row['service_product_id'] ?? null))
            ->values();

        $total = round((float) ($data['total_value'] ?? 0), 2);
        if ($lines->isNotEmpty()) {
            $total = round($lines->sum(fn (array $l): float => (float) ($l['line_total'] ?? 0)), 2);
        }

        DB::transaction(function () use ($tenant, $supplier, $data, $lines, $total): void {
            $payload = [
                'company_id' => $tenant->id,
                'supplier_id' => $supplier->id,
                'currency_id' => $data['currency_id'] ?? null,
                'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
                'order_date' => $data['order_date'],
                'due_date' => $data['due_date'],
                'total_value' => $total,
                'line_kind' => $data['line_kind'] ?? 'services',
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'invoice_text_id' => $data['invoice_text_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ];

            if (! empty($data['id'])) {
                $order = PurchaseOrder::query()
                    ->where('company_id', $tenant->id)
                    ->findOrFail((int) $data['id']);
                $order->update($payload);
                $order->lines()->delete();
            } else {
                $payload['order_number'] = (int) ($data['order_number'] ?? PurchaseOrder::nextOrderNumber($tenant->id));
                $payload['user_id'] = Auth::id();
                $order = PurchaseOrder::query()->create($payload);
            }

            foreach ($lines as $index => $line) {
                PurchaseOrderLine::query()->create([
                    'purchase_order_id' => $order->id,
                    'service_product_id' => $line['service_product_id'] ?? null,
                    'description' => $line['description'] ?? null,
                    'quantity' => (float) ($line['quantity'] ?? 1),
                    'unit_price' => (float) ($line['unit_price'] ?? 0),
                    'line_total' => (float) ($line['line_total'] ?? 0),
                    'sort_order' => $index,
                ]);
            }
        });

        Notification::make()->title('تم الحفظ')->success()->send();

        $this->redirect(PurchaseOrderPage::getUrl());
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
                ->url(fn (): string => PurchaseOrderPage::getUrl()),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'امر الشراء';
    }
}
