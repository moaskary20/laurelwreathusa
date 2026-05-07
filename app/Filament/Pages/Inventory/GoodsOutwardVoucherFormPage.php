<?php

namespace App\Filament\Pages\Inventory;

use App\Filament\Pages\Concerns\InteractsWithFormActions;
use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\Customer;
use App\Models\GoodsOutwardVoucher;
use App\Models\SalesOrder;
use App\Services\Accounting\ChartOfAccountsService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormInlineAction;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

final class GoodsOutwardVoucherFormPage extends Page
{
    use InteractsWithFormActions;

    protected static ?string $slug = 'goods-outward-voucher-form';

    protected static string $view = 'filament.pages.inventory.goods-outward-voucher-form';

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
            $voucher = GoodsOutwardVoucher::query()
                ->where('company_id', $tenant->id)
                ->findOrFail((int) $editId);

            $this->form->fill([
                'id' => $voucher->id,
                'voucher_number' => $voucher->voucher_number,
                'voucher_date' => $voucher->voucher_date?->toDateTimeString(),
                'customer_id' => $voucher->customer_id,
                'sales_order_id' => $voucher->sales_order_id,
                'account_group_id' => $voucher->account_group_id,
            ]);
        } else {
            $this->form->fill([
                'voucher_number' => GoodsOutwardVoucher::nextVoucherNumber($tenant->id),
                'voucher_date' => now()->toDateTimeString(),
            ]);
        }

        $this->bootedInteractsWithFormActions();
    }

    public function form(Form $form): Form
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        return $form
            ->schema([
                Forms\Components\Hidden::make('id'),
                Forms\Components\TextInput::make('voucher_number')
                    ->label('رقم السند')
                    ->disabled()
                    ->dehydrated(true),
                Forms\Components\DateTimePicker::make('voucher_date')
                    ->label('تاريخ الفاتورة')
                    ->required()
                    ->native(false)
                    ->seconds(false),
                Forms\Components\Select::make('customer_id')
                    ->label('العميل')
                    ->placeholder('اختيار عميل')
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
                    ->afterStateUpdated(function (Set $set): void {
                        $set('sales_order_id', null);
                    }),
                Forms\Components\Select::make('sales_order_id')
                    ->label('رقم امر البيع')
                    ->placeholder('اختيار الكود')
                    ->options(function () use ($tenant): array {
                        $cid = $this->data['customer_id'] ?? null;
                        if (! $cid) {
                            return [];
                        }

                        return SalesOrder::query()
                            ->where('company_id', $tenant->id)
                            ->where('customer_id', $cid)
                            ->orderByDesc('order_date')
                            ->get()
                            ->mapWithKeys(fn (SalesOrder $o): array => [$o->id => (string) $o->order_number])
                            ->all();
                    })
                    ->searchable()
                    ->preload()
                    ->native(false),
                Forms\Components\Select::make('account_group_id')
                    ->label('الحساب المدين')
                    ->placeholder('المجموعات')
                    ->options(AccountGroup::indentedPostingOptionsForCompany($tenant->id))
                    ->searchable()
                    ->preload()
                    ->native(false),
                Forms\Components\Actions::make([
                    FormInlineAction::make('searchStub')
                        ->label('بحث')
                        ->icon('heroicon-m-magnifying-glass')
                        ->color('gray')
                        ->disabled()
                        ->tooltip('سيتم ربط تفاصيل البنود لاحقاً'),
                ]),
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

        Customer::query()
            ->where('company_id', $tenant->id)
            ->whereKey($data['customer_id'])
            ->firstOrFail();

        if (! empty($data['sales_order_id'])) {
            SalesOrder::query()
                ->where('company_id', $tenant->id)
                ->where('customer_id', $data['customer_id'])
                ->whereKey($data['sales_order_id'])
                ->firstOrFail();
        }

        if (! empty($data['account_group_id'])) {
            app(ChartOfAccountsService::class)->assertCanPostToAccount($tenant->id, (int) $data['account_group_id']);
        }

        $payload = [
            'company_id' => $tenant->id,
            'customer_id' => $data['customer_id'],
            'voucher_date' => $data['voucher_date'],
            'sales_order_id' => $data['sales_order_id'] ?? null,
            'account_group_id' => $data['account_group_id'] ?? null,
        ];

        if (! empty($data['id'])) {
            GoodsOutwardVoucher::query()
                ->where('company_id', $tenant->id)
                ->whereKey((int) $data['id'])
                ->update($payload);
        } else {
            $payload['voucher_number'] = (int) ($data['voucher_number'] ?? GoodsOutwardVoucher::nextVoucherNumber($tenant->id));
            $payload['user_id'] = Auth::id();
            GoodsOutwardVoucher::query()->create($payload);
        }

        Notification::make()->title('تم الحفظ')->success()->send();

        $this->redirect(GoodsOutwardVoucherPage::getUrl());
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
                ->url(fn (): string => GoodsOutwardVoucherPage::getUrl()),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'سند اخراج بضاعه';
    }
}
