<?php

namespace App\Filament\Pages\Accounting;

use App\Filament\Pages\Concerns\InteractsWithFormActions;
use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ReceiptVoucher;
use App\Models\ReceiptVoucherLine;
use App\Services\Accounting\ChartOfAccountsService;
use App\Services\Ledger\ReceiptVoucherLedgerSync;
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

final class ReceiptVoucherFormPage extends Page
{
    use InteractsWithFormActions;

    protected static ?string $slug = 'receipt-voucher-form';

    protected static string $view = 'filament.pages.accounting.receipt-voucher-form';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'سند قبض';

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
            $voucher = ReceiptVoucher::query()
                ->where('company_id', $tenant->id)
                ->with(['lines'])
                ->findOrFail((int) $editId);

            $this->form->fill($this->voucherToFormData($voucher));
        } else {
            $this->form->fill([
                'receipt_number' => ReceiptVoucher::nextReceiptNumber($tenant->id),
                'receipt_date' => now()->toDateString(),
                'customer_id' => null,
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
    protected function voucherToFormData(ReceiptVoucher $voucher): array
    {
        return [
            'id' => $voucher->id,
            'receipt_number' => $voucher->receipt_number,
            'receipt_date' => $voucher->receipt_date?->format('Y-m-d'),
            'customer_id' => $voucher->customer_id,
            'payment_method' => $voucher->payment_method,
            'payment_kind' => $voucher->payment_kind,
            'account_group_id' => $voucher->account_group_id,
            'total_amount' => $voucher->total_amount,
            'description' => $voucher->description,
            'lines' => $voucher->lines->map(fn (ReceiptVoucherLine $line, int $i): array => [
                'invoice_id' => $line->invoice_id,
                'amount' => $line->amount,
                'sort_order' => $line->sort_order ?: $i,
            ])->values()->all(),
        ];
    }

    public function form(Form $form): Form
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $accountOptions = AccountGroup::indentedPostingOptionsForCompany($tenant->id);

        return $form
            ->schema([
                Forms\Components\Hidden::make('id'),
                Forms\Components\Hidden::make('receipt_number')
                    ->dehydrated(true),
                Forms\Components\Grid::make(1)
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('اسم العميل')
                            ->required()
                            ->options(
                                Customer::query()
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
                        Forms\Components\DatePicker::make('receipt_date')
                            ->label('تاريخ سند القبض')
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
                    FormInlineAction::make('searchInvoices')
                        ->label('بحث')
                        ->icon('heroicon-m-magnifying-glass')
                        ->color('gray')
                        ->visible(fn (Get $get): bool => ($get('payment_kind') ?? 'advance') === 'settlement')
                        ->action(fn () => $this->notifyInvoiceCount()),
                ]),
                Forms\Components\Repeater::make('lines')
                    ->label('تسديد الفواتير')
                    ->schema([
                        Forms\Components\Select::make('invoice_id')
                            ->label('رقم الفاتورة')
                            ->required()
                            ->options(function (): array {
                                $tenant = Filament::getTenant();
                                if (! $tenant instanceof Company) {
                                    return [];
                                }
                                $cid = $this->data['customer_id'] ?? null;
                                if (! $cid) {
                                    return [];
                                }

                                return Invoice::query()
                                    ->where('company_id', $tenant->id)
                                    ->where('customer_id', $cid)
                                    ->orderByDesc('invoice_date')
                                    ->get()
                                    ->mapWithKeys(fn (Invoice $inv): array => [
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

    public function notifyInvoiceCount(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $cid = $this->data['customer_id'] ?? null;
        if (! $cid) {
            Notification::make()->title('اختر العميل أولاً')->warning()->send();

            return;
        }

        $count = Invoice::query()
            ->where('company_id', $tenant->id)
            ->where('customer_id', $cid)
            ->count();

        Notification::make()
            ->title('نتائج البحث')
            ->body('عدد فواتير العميل المتاحة: '.$count)
            ->success()
            ->send();
    }

    public function save(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $this->form->validate();
        $data = $this->form->getState();

        $customer = Customer::query()
            ->where('company_id', $tenant->id)
            ->whereKey($data['customer_id'])
            ->firstOrFail();

        $kind = $data['payment_kind'] ?? 'advance';

        /** @var Collection<int, array<string, mixed>> $settlementLines */
        $settlementLines = collect();

        if ($kind === 'settlement') {
            $settlementLines = collect($data['lines'] ?? [])
                ->filter(fn (array $row): bool => filled($row['invoice_id'] ?? null))
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
                Invoice::query()
                    ->where('company_id', $tenant->id)
                    ->where('customer_id', $customer->id)
                    ->whereKey((int) $line['invoice_id'])
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

        if (! empty($data['account_group_id'])) {
            app(ChartOfAccountsService::class)->assertCanPostToAccount($tenant->id, (int) $data['account_group_id']);
        }

        DB::transaction(function () use ($tenant, $customer, $data, $kind, $settlementLines): void {
            $payload = [
                'company_id' => $tenant->id,
                'user_id' => Auth::id(),
                'receipt_date' => $data['receipt_date'],
                'customer_id' => $customer->id,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_kind' => $kind,
                'account_group_id' => $data['account_group_id'] ?? null,
                'total_amount' => $data['total_amount'],
                'description' => $data['description'] ?? null,
            ];

            if (! empty($data['id'])) {
                $voucher = ReceiptVoucher::query()
                    ->where('company_id', $tenant->id)
                    ->findOrFail((int) $data['id']);
                $payload['receipt_number'] = $voucher->receipt_number;
                $voucher->update($payload);
                $voucher->lines()->delete();
            } else {
                $payload['receipt_number'] = (int) ($data['receipt_number'] ?? ReceiptVoucher::nextReceiptNumber($tenant->id));
                $voucher = ReceiptVoucher::query()->create($payload);
            }

            if ($kind === 'settlement') {
                foreach ($settlementLines as $index => $line) {
                    ReceiptVoucherLine::query()->create([
                        'receipt_voucher_id' => $voucher->id,
                        'invoice_id' => (int) $line['invoice_id'],
                        'amount' => (float) ($line['amount'] ?? 0),
                        'sort_order' => $index,
                    ]);
                }
            }

            $voucher->refresh();
            app(ReceiptVoucherLedgerSync::class)->sync($voucher);
        });

        Notification::make()->title('تم الحفظ')->success()->send();

        $this->redirect(ReceiptVouchersPage::getUrl(['tenant' => $tenant]));
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
                ->url(fn (): string => ReceiptVouchersPage::getUrl(['tenant' => Filament::getTenant()])),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'سند قبض';
    }
}
