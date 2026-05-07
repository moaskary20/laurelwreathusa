<?php

namespace App\Filament\Pages\Accounting;

use App\Filament\Pages\Concerns\InteractsWithFormActions;
use App\Models\AccountGroup;
use App\Models\BankDeposit;
use App\Models\Company;
use App\Models\Currency;
use App\Services\Accounting\ChartOfAccountsService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class BankDepositFormPage extends Page
{
    use InteractsWithFormActions;

    protected static ?string $slug = 'bank-deposit-form';

    protected static string $view = 'filament.pages.accounting.bank-deposit-form';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'ايداع بنكي';

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
            $deposit = BankDeposit::query()
                ->where('company_id', $tenant->id)
                ->findOrFail((int) $editId);

            $this->form->fill($this->depositToFormData($deposit));
        } else {
            $this->form->fill([
                'deposit_number' => BankDeposit::nextDepositNumber($tenant->id),
                'deposit_date' => now()->toDateString(),
                'from_account_group_id' => null,
                'to_account_group_id' => null,
                'currency_id' => null,
                'amount' => 0,
                'description' => null,
            ]);
        }

        $this->bootedInteractsWithFormActions();
    }

    /**
     * @return array<string, mixed>
     */
    protected function depositToFormData(BankDeposit $deposit): array
    {
        return [
            'id' => $deposit->id,
            'deposit_number' => $deposit->deposit_number,
            'deposit_date' => $deposit->deposit_date?->format('Y-m-d'),
            'from_account_group_id' => $deposit->from_account_group_id,
            'to_account_group_id' => $deposit->to_account_group_id,
            'currency_id' => $deposit->currency_id,
            'amount' => $deposit->amount,
            'description' => $deposit->description,
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
                Forms\Components\Hidden::make('deposit_number')
                    ->dehydrated(true),
                Forms\Components\DatePicker::make('deposit_date')
                    ->label('التاريخ')
                    ->required()
                    ->native(false)
                    ->displayFormat('Y/m/d'),
                Forms\Components\Select::make('from_account_group_id')
                    ->label('من حساب')
                    ->required()
                    ->options($accountOptions)
                    ->searchable()
                    ->preload()
                    ->native(false),
                Forms\Components\Select::make('to_account_group_id')
                    ->label('الى حساب')
                    ->required()
                    ->options($accountOptions)
                    ->searchable()
                    ->preload()
                    ->native(false),
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
                Forms\Components\TextInput::make('amount')
                    ->label('المبلغ')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->minValue(0)
                    ->live(onBlur: true),
                Forms\Components\Placeholder::make('total_display')
                    ->label('المجموع النهائي')
                    ->content(function (Get $get): string {
                        return number_format((float) ($get('amount') ?? 0), 2);
                    }),
                Forms\Components\Textarea::make('description')
                    ->label('الوصف')
                    ->rows(4)
                    ->columnSpanFull(),
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

        $fromId = (int) $data['from_account_group_id'];
        $toId = (int) $data['to_account_group_id'];
        if ($fromId === $toId) {
            Notification::make()->title('يجب أن يكون حساب المصدر مختلفاً عن حساب الوجهة')->danger()->send();

            return;
        }

        abort_unless(
            AccountGroup::query()->where('company_id', $tenant->id)->whereKey($fromId)->exists(),
            404
        );
        abort_unless(
            AccountGroup::query()->where('company_id', $tenant->id)->whereKey($toId)->exists(),
            404
        );

        app(ChartOfAccountsService::class)->assertCanPostToAccount($tenant->id, $fromId);
        app(ChartOfAccountsService::class)->assertCanPostToAccount($tenant->id, $toId);

        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            Notification::make()->title('أدخل مبلغاً أكبر من الصفر')->danger()->send();

            return;
        }

        DB::transaction(function () use ($tenant, $data, $fromId, $toId, $amount): void {
            $payload = [
                'company_id' => $tenant->id,
                'user_id' => Auth::id(),
                'deposit_date' => $data['deposit_date'],
                'from_account_group_id' => $fromId,
                'to_account_group_id' => $toId,
                'currency_id' => $data['currency_id'] ?? null,
                'amount' => $amount,
                'description' => $data['description'] ?? null,
            ];

            if (! empty($data['id'])) {
                $deposit = BankDeposit::query()
                    ->where('company_id', $tenant->id)
                    ->findOrFail((int) $data['id']);
                $payload['deposit_number'] = $deposit->deposit_number;
                $deposit->update($payload);
            } else {
                $payload['deposit_number'] = (int) ($data['deposit_number'] ?? BankDeposit::nextDepositNumber($tenant->id));
                BankDeposit::query()->create($payload);
            }
        });

        Notification::make()->title('تم الحفظ')->success()->send();

        $this->redirect(BankDepositPage::getUrl(['tenant' => $tenant]));
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
            Action::make('cancel')
                ->label('إلغاء')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->url(fn (): string => BankDepositPage::getUrl(['tenant' => Filament::getTenant()])),
            Action::make('print')
                ->label('طباعه')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn () => $this->js('window.print()')),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'ايداع بنكي';
    }
}
