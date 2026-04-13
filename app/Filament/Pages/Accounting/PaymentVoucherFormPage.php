<?php

namespace App\Filament\Pages\Accounting;

use App\Filament\Pages\Concerns\InteractsWithFormActions;
use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\PaymentVoucher;
use App\Models\PaymentVoucherLine;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Services\Ledger\PaymentVoucherLedgerSync;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormInlineAction;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class PaymentVoucherFormPage extends Page
{
    use InteractsWithFormActions;

    protected static ?string $slug = 'payment-voucher-form';

    protected static string $view = 'filament.pages.accounting.payment-voucher-form';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'سند صرف';

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
            $voucher = PaymentVoucher::query()
                ->where('company_id', $tenant->id)
                ->with(['lines'])
                ->findOrFail((int) $editId);

            $this->form->fill($this->voucherToFormData($voucher));
        } else {
            $this->form->fill([
                'payment_number' => PaymentVoucher::nextPaymentNumber($tenant->id),
                'payment_date' => now()->toDateString(),
                'supplier_id' => null,
                'payment_method' => 'cash',
                'payment_kind' => 'advance',
                'account_group_id' => null,
                'total_amount' => 0,
                'lines' => [],
                'description' => null,
            ]);
        }

        $this->bootedInteractsWithFormActions();
    }

    /**
     * @return array<string, mixed>
     */
    protected function voucherToFormData(PaymentVoucher $voucher): array
    {
        return [
            'id' => $voucher->id,
            'payment_number' => $voucher->payment_number,
            'payment_date' => $voucher->payment_date?->format('Y-m-d'),
            'supplier_id' => $voucher->supplier_id,
            'payment_method' => $voucher->payment_method,
            'payment_kind' => $voucher->payment_kind,
            'account_group_id' => $voucher->account_group_id,
            'total_amount' => $voucher->total_amount,
            'description' => $voucher->description,
            'lines' => $voucher->lines->map(fn (PaymentVoucherLine $line, int $i): array => [
                'purchase_invoice_id' => $line->purchase_invoice_id,
                'amount' => $line->amount,
                'sort_order' => $line->sort_order ?: $i,
            ])->values()->all(),
        ];
    }

    public function form(Form $form): Form
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $accountOptions = AccountGroup::indentedOptionsForCompany($tenant->id);

        return $form
            ->schema([
                Forms\Components\Hidden::make('id'),
                Forms\Components\Hidden::make('payment_number')
                    ->dehydrated(true),
                Forms\Components\Grid::make(1)
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->label('اسم المورد')
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
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('تاريخ سند الصرف')
                            ->required()
                            ->native(false)
                            ->displayFormat('Y/m/d'),
                        Forms\Components\Select::make('payment_method')
                            ->label('طريقة الدفع')
                            ->options([
                                'cash' => 'نقدي',
                                'bank' => 'بنك',
                                'cheque' => 'شيك',
                                'card' => 'بطاقة',
                            ])
                            ->native(false),
                        Forms\Components\Select::make('account_group_id')
                            ->label('المجموعات')
                            ->options($accountOptions)
                            ->searchable()
                            ->preload()
                            ->native(false),
                    ]),
                Forms\Components\Radio::make('payment_kind')
                    ->label('نوع الدفعة')
                    ->options([
                        'advance' => 'دفعة مقدمة',
                        'settlement' => 'تسديد الفواتير',
                    ])
                    ->inline()
                    ->live()
                    ->default('advance'),
                Forms\Components\TextInput::make('total_amount')
                    ->label('المبلغ')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->visible(fn (Get $get): bool => ($get('payment_kind') ?? 'advance') === 'advance')
                    ->required(fn (Get $get): bool => ($get('payment_kind') ?? 'advance') === 'advance'),
                Forms\Components\Actions::make([
                    FormInlineAction::make('searchPurchaseInvoices')
                        ->label('بحث')
                        ->icon('heroicon-m-magnifying-glass')
                        ->color('gray')
                        ->visible(fn (Get $get): bool => ($get('payment_kind') ?? 'advance') === 'settlement')
                        ->action(fn () => $this->notifyPurchaseInvoiceCount()),
                ]),
                Forms\Components\Repeater::make('lines')
                    ->label('تسديد فواتير المشتريات')
                    ->schema([
                        Forms\Components\Select::make('purchase_invoice_id')
                            ->label('رقم الفاتورة')
                            ->required()
                            ->options(function (): array {
                                $tenant = Filament::getTenant();
                                if (! $tenant instanceof Company) {
                                    return [];
                                }
                                $sid = $this->data['supplier_id'] ?? null;
                                if (! $sid) {
                                    return [];
                                }

                                return PurchaseInvoice::query()
                                    ->where('company_id', $tenant->id)
                                    ->where('supplier_id', $sid)
                                    ->orderByDesc('invoice_date')
                                    ->get()
                                    ->mapWithKeys(fn (PurchaseInvoice $inv): array => [
                                        $inv->id => 'فاتورة رقم '.$inv->invoice_number.' — '.number_format((float) $inv->grand_total, 2),
                                    ])
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Forms\Components\TextInput::make('amount')
                            ->label('المبلغ')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->minValue(0),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get): bool => ($get('payment_kind') ?? 'advance') === 'settlement')
                    ->addActionLabel('اضافه سطر +')
                    ->defaultItems(0)
                    ->collapsible(),
                Forms\Components\Placeholder::make('settlement_total')
                    ->label('إجمالي تسديد الفواتير')
                    ->content(function (Get $get): string {
                        if (($get('payment_kind') ?? '') !== 'settlement') {
                            return '—';
                        }
                        $lines = $get('lines') ?? [];
                        $s = 0.0;
                        foreach ($lines as $ln) {
                            $s += (float) ($ln['amount'] ?? 0);
                        }

                        return number_format($s, 2);
                    })
                    ->visible(fn (Get $get): bool => ($get('payment_kind') ?? 'advance') === 'settlement'),
                Forms\Components\Textarea::make('description')
                    ->label('الوصف')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(1)
            ->statePath('data');
    }

    public function notifyPurchaseInvoiceCount(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $sid = $this->data['supplier_id'] ?? null;
        if (! $sid) {
            Notification::make()->title('اختر المورد أولاً')->warning()->send();

            return;
        }

        $count = PurchaseInvoice::query()
            ->where('company_id', $tenant->id)
            ->where('supplier_id', $sid)
            ->count();

        Notification::make()
            ->title('نتائج البحث')
            ->body('عدد فواتير المورد المتاحة: '.$count)
            ->success()
            ->send();
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

        $kind = $data['payment_kind'] ?? 'advance';

        /** @var Collection<int, array<string, mixed>> $settlementLines */
        $settlementLines = collect();

        if ($kind === 'settlement') {
            $settlementLines = collect($data['lines'] ?? [])
                ->filter(fn (array $row): bool => filled($row['purchase_invoice_id'] ?? null))
                ->values();

            if ($settlementLines->isEmpty()) {
                Notification::make()->title('أضف بنداً واحداً على الأقل لتسديد الفواتير')->danger()->send();

                return;
            }

            $total = round($settlementLines->sum(fn (array $l): float => (float) ($l['amount'] ?? 0)), 2);
            if ($total <= 0) {
                Notification::make()->title('مجموع المبالغ يجب أن يكون أكبر من الصفر')->danger()->send();

                return;
            }

            foreach ($settlementLines as $line) {
                PurchaseInvoice::query()
                    ->where('company_id', $tenant->id)
                    ->where('supplier_id', $supplier->id)
                    ->whereKey((int) $line['purchase_invoice_id'])
                    ->firstOrFail();
            }

            $data['total_amount'] = $total;
        } else {
            $total = (float) ($data['total_amount'] ?? 0);
            if ($total <= 0) {
                Notification::make()->title('أدخل المبلغ')->danger()->send();

                return;
            }
            $data['total_amount'] = round($total, 2);
        }

        DB::transaction(function () use ($tenant, $supplier, $data, $kind, $settlementLines): void {
            $payload = [
                'company_id' => $tenant->id,
                'user_id' => Auth::id(),
                'payment_date' => $data['payment_date'],
                'supplier_id' => $supplier->id,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_kind' => $kind,
                'account_group_id' => $data['account_group_id'] ?? null,
                'total_amount' => $data['total_amount'],
                'description' => $data['description'] ?? null,
            ];

            if (! empty($data['id'])) {
                $voucher = PaymentVoucher::query()
                    ->where('company_id', $tenant->id)
                    ->findOrFail((int) $data['id']);
                $payload['payment_number'] = $voucher->payment_number;
                $voucher->update($payload);
                $voucher->lines()->delete();
            } else {
                $payload['payment_number'] = (int) ($data['payment_number'] ?? PaymentVoucher::nextPaymentNumber($tenant->id));
                $voucher = PaymentVoucher::query()->create($payload);
            }

            if ($kind === 'settlement') {
                foreach ($settlementLines as $index => $line) {
                    PaymentVoucherLine::query()->create([
                        'payment_voucher_id' => $voucher->id,
                        'purchase_invoice_id' => (int) $line['purchase_invoice_id'],
                        'amount' => (float) ($line['amount'] ?? 0),
                        'sort_order' => $index,
                    ]);
                }
            }

            $voucher->refresh();
            app(PaymentVoucherLedgerSync::class)->sync($voucher);
        });

        Notification::make()->title('تم الحفظ')->success()->send();

        $this->redirect(PaymentVouchersPage::getUrl(['tenant' => $tenant]));
    }

    /**
     * @return array<\Filament\Actions\Action>
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
                ->url(fn (): string => PaymentVouchersPage::getUrl(['tenant' => Filament::getTenant()])),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'سند صرف';
    }
}
