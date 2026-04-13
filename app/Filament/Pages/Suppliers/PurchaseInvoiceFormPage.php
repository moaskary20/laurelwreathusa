<?php

namespace App\Filament\Pages\Suppliers;

use App\Filament\Pages\Concerns\InteractsWithFormActions;
use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Currency;
use App\Models\InvoiceText;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use App\Models\PurchaseOrder;
use App\Services\Inventory\InvoiceProductStockSync;
use App\Services\Ledger\PurchaseInvoiceLedgerSync;
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

final class PurchaseInvoiceFormPage extends Page
{
    use InteractsWithFormActions;

    protected static ?string $slug = 'purchase-invoice-form';

    protected static string $view = 'filament.pages.suppliers.purchase-invoice-form';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'فاتورة المشتريات';

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
            $invoice = PurchaseInvoice::query()
                ->where('company_id', $tenant->id)
                ->with(['lines', 'supplier'])
                ->findOrFail((int) $editId);

            $this->form->fill($this->purchaseInvoiceToFormData($invoice));
        } else {
            $this->form->fill([
                'invoice_number' => PurchaseInvoice::nextInvoiceNumber($tenant->id),
                'invoice_date' => now()->toDateTimeString(),
                'due_date' => now()->toDateString(),
                'line_kind' => 'services',
                'lines' => [],
                'discount_amount' => 0,
                'tax_amount' => 0,
                'subtotal_before_discount' => 0,
                'total_after_discount' => 0,
                'grand_total' => 0,
            ]);
        }

        $this->bootedInteractsWithFormActions();
    }

    /**
     * @return array<string, mixed>
     */
    protected function purchaseInvoiceToFormData(PurchaseInvoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'supplier_invoice_number' => $invoice->supplier_invoice_number,
            'supplier_id' => $invoice->supplier_id,
            'currency_id' => $invoice->currency_id,
            'purchase_order_id' => $invoice->purchase_order_id,
            'entry_voucher_reference' => $invoice->entry_voucher_reference,
            'invoice_date' => $invoice->invoice_date?->toDateTimeString(),
            'due_date' => $invoice->due_date?->format('Y-m-d'),
            'line_kind' => $invoice->line_kind,
            'subtotal_before_discount' => $invoice->subtotal_before_discount,
            'discount_amount' => $invoice->discount_amount,
            'total_after_discount' => $invoice->total_after_discount,
            'tax_amount' => $invoice->tax_amount,
            'grand_total' => $invoice->grand_total,
            'total_in_words' => $invoice->total_in_words,
            'bank_account_id' => $invoice->bank_account_id,
            'invoice_text_id' => $invoice->invoice_text_id,
            'notes' => $invoice->notes,
            'lines' => $invoice->lines->map(fn (PurchaseInvoiceLine $line, int $index): array => [
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
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('purchase_order_id', null);
                            }),
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
                        Forms\Components\Select::make('purchase_order_id')
                            ->label('رقم امر الشراء')
                            ->placeholder('اختيار رقم امر الشراء')
                            ->options(function () use ($tenant): array {
                                $sid = $this->data['supplier_id'] ?? null;
                                if (! $sid) {
                                    return [];
                                }

                                return PurchaseOrder::query()
                                    ->where('company_id', $tenant->id)
                                    ->where('supplier_id', $sid)
                                    ->orderByDesc('order_date')
                                    ->get()
                                    ->mapWithKeys(fn (PurchaseOrder $o): array => [$o->id => (string) $o->order_number])
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Forms\Components\TextInput::make('entry_voucher_reference')
                            ->label('رقم سند الادخال')
                            ->maxLength(100),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('رقم الفاتورة')
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\TextInput::make('supplier_invoice_number')
                            ->label('رقم فاتورة المورد')
                            ->maxLength(100),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\DateTimePicker::make('invoice_date')
                            ->label('تاريخ الفاتورة')
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
                    ->label('بنود فاتورة المشتريات')
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
                Forms\Components\Section::make('المجموع')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal_before_discount')
                            ->label('المجموع قبل الخصم والضريبة')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\TextInput::make('discount_amount')
                            ->label('قيمة الخصم')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn () => $this->applySummaryOnly()),
                        Forms\Components\TextInput::make('total_after_discount')
                            ->label('الاجمالي بعد الخصم')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\TextInput::make('tax_amount')
                            ->label('الضريبة')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn () => $this->applySummaryOnly()),
                        Forms\Components\TextInput::make('grand_total')
                            ->label('المجموع')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\Textarea::make('total_in_words')
                            ->label('المجموع كتابة')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
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
            ])
            ->columns(1)
            ->statePath('data');
    }

    public function calculateTotals(): void
    {
        $this->recomputeTotalsFromLines(sendNotification: true);
    }

    protected function recomputeTotalsFromLines(bool $sendNotification = false): void
    {
        $lines = $this->data['lines'] ?? [];
        $subtotal = 0.0;
        foreach ($lines as $i => $line) {
            $qty = (float) ($line['quantity'] ?? 0);
            $price = (float) ($line['unit_price'] ?? 0);
            $lineTotal = round($qty * $price, 2);
            $lines[$i]['line_total'] = $lineTotal;
            $subtotal += $lineTotal;
        }
        $this->data['lines'] = $lines;
        $this->data['subtotal_before_discount'] = round($subtotal, 2);
        $this->applySummaryOnly();

        if ($sendNotification) {
            Notification::make()->title('تم تحديث الاحتساب')->success()->send();
        }
    }

    protected function applySummaryOnly(): void
    {
        $subtotal = (float) ($this->data['subtotal_before_discount'] ?? 0);
        $discount = (float) ($this->data['discount_amount'] ?? 0);
        $after = round(max(0, $subtotal - $discount), 2);
        $this->data['total_after_discount'] = $after;
        $tax = (float) ($this->data['tax_amount'] ?? 0);
        $this->data['grand_total'] = round($after + $tax, 2);
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

        if (! empty($data['purchase_order_id'])) {
            PurchaseOrder::query()
                ->where('company_id', $tenant->id)
                ->where('supplier_id', $supplier->id)
                ->whereKey($data['purchase_order_id'])
                ->firstOrFail();
        }

        $this->recomputeTotalsFromLines(sendNotification: false);
        $data = $this->form->getState();

        $lines = collect($data['lines'] ?? [])
            ->filter(fn (array $row): bool => filled($row['description'] ?? null) || filled($row['service_product_id'] ?? null))
            ->values();

        $previousLines = collect();
        if (! empty($data['id'])) {
            $previousLines = PurchaseInvoiceLine::query()
                ->where('purchase_invoice_id', (int) $data['id'])
                ->with('serviceProduct')
                ->get();
        }

        DB::transaction(function () use ($tenant, $supplier, $data, $lines, $previousLines): void {
            $payload = [
                'company_id' => $tenant->id,
                'supplier_id' => $supplier->id,
                'currency_id' => $data['currency_id'] ?? null,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'entry_voucher_reference' => $data['entry_voucher_reference'] ?? null,
                'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'],
                'line_kind' => $data['line_kind'] ?? 'services',
                'subtotal_before_discount' => (float) ($data['subtotal_before_discount'] ?? 0),
                'discount_amount' => (float) ($data['discount_amount'] ?? 0),
                'total_after_discount' => (float) ($data['total_after_discount'] ?? 0),
                'tax_amount' => (float) ($data['tax_amount'] ?? 0),
                'grand_total' => (float) ($data['grand_total'] ?? 0),
                'total_in_words' => $data['total_in_words'] ?? null,
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'invoice_text_id' => $data['invoice_text_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ];

            if (! empty($data['id'])) {
                $invoice = PurchaseInvoice::query()
                    ->where('company_id', $tenant->id)
                    ->findOrFail((int) $data['id']);
                $invoice->update($payload);
                $invoice->lines()->delete();
            } else {
                $payload['invoice_number'] = (int) ($data['invoice_number'] ?? PurchaseInvoice::nextInvoiceNumber($tenant->id));
                $payload['user_id'] = Auth::id();
                $invoice = PurchaseInvoice::query()->create($payload);
            }

            foreach ($lines as $index => $line) {
                PurchaseInvoiceLine::query()->create([
                    'purchase_invoice_id' => $invoice->id,
                    'service_product_id' => $line['service_product_id'] ?? null,
                    'description' => $line['description'] ?? null,
                    'quantity' => (float) ($line['quantity'] ?? 1),
                    'unit_price' => (float) ($line['unit_price'] ?? 0),
                    'line_total' => (float) ($line['line_total'] ?? 0),
                    'sort_order' => $index,
                ]);
            }

            $invoice->refresh();
            $invoice->load('lines.serviceProduct');

            app(PurchaseInvoiceLedgerSync::class)->sync($invoice);
            app(InvoiceProductStockSync::class)->syncPurchaseInvoice($invoice, $previousLines, $tenant);
        });

        Notification::make()->title('تم الحفظ')->success()->send();

        $this->redirect(PurchaseInvoicesPage::getUrl());
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
                ->url(fn (): string => PurchaseInvoicesPage::getUrl()),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'فاتورة المشتريات';
    }
}
