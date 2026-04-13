<?php

namespace App\Filament\Pages\Accounting;

use App\Filament\Pages\Concerns\InteractsWithFormActions;
use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Supplier;
use App\Services\Ledger\CreditNoteLedgerSync;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class CreditNoteFormPage extends Page
{
    use InteractsWithFormActions;

    protected static ?string $slug = 'credit-note-form';

    protected static string $view = 'filament.pages.accounting.credit-note-form';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'اشعار دائن';

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
            $note = CreditNote::query()
                ->where('company_id', $tenant->id)
                ->findOrFail((int) $editId);

            $this->form->fill($this->noteToFormData($note));
        } else {
            $this->form->fill([
                'document_number' => CreditNote::nextDocumentNumber($tenant->id),
                'document_date' => now()->toDateString(),
                'counterparty_type' => CreditNote::TYPE_CUSTOMER,
                'customer_id' => null,
                'supplier_id' => null,
                'currency_id' => null,
                'account_group_id' => null,
                'amount' => 0,
                'description' => null,
            ]);
        }

        $this->bootedInteractsWithFormActions();
    }

    /**
     * @return array<string, mixed>
     */
    protected function noteToFormData(CreditNote $note): array
    {
        return [
            'id' => $note->id,
            'document_number' => $note->document_number,
            'document_date' => $note->document_date?->format('Y-m-d'),
            'counterparty_type' => $note->counterparty_type,
            'customer_id' => $note->customer_id,
            'supplier_id' => $note->supplier_id,
            'currency_id' => $note->currency_id,
            'account_group_id' => $note->account_group_id,
            'amount' => $note->amount,
            'description' => $note->description,
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
                Forms\Components\Hidden::make('document_number')
                    ->dehydrated(true),
                Forms\Components\Radio::make('counterparty_type')
                    ->label('عميل / مورد')
                    ->options(CreditNote::counterpartyTypeOptions())
                    ->inline()
                    ->live()
                    ->default(CreditNote::TYPE_CUSTOMER)
                    ->afterStateUpdated(function ($state, Set $set): void {
                        if ($state === CreditNote::TYPE_CUSTOMER) {
                            $set('supplier_id', null);
                        } else {
                            $set('customer_id', null);
                        }
                    }),
                Forms\Components\Select::make('customer_id')
                    ->label('اسم العميل')
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
                    ->visible(fn (Get $get): bool => ($get('counterparty_type') ?? CreditNote::TYPE_CUSTOMER) === CreditNote::TYPE_CUSTOMER)
                    ->required(fn (Get $get): bool => ($get('counterparty_type') ?? '') === CreditNote::TYPE_CUSTOMER),
                Forms\Components\Select::make('supplier_id')
                    ->label('اسم المورد')
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
                    ->visible(fn (Get $get): bool => ($get('counterparty_type') ?? '') === CreditNote::TYPE_SUPPLIER)
                    ->required(fn (Get $get): bool => ($get('counterparty_type') ?? '') === CreditNote::TYPE_SUPPLIER),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\DatePicker::make('document_date')
                            ->label('التاريخ')
                            ->required()
                            ->native(false)
                            ->displayFormat('Y/m/d'),
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
                Forms\Components\Select::make('account_group_id')
                    ->label('المجموعات')
                    ->options($accountOptions)
                    ->searchable()
                    ->preload()
                    ->native(false),
                Forms\Components\TextInput::make('amount')
                    ->label('لقد قيدنا لحسابكم مبلغ')
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

        $type = $data['counterparty_type'] ?? CreditNote::TYPE_CUSTOMER;
        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            Notification::make()->title('أدخل مبلغاً أكبر من الصفر')->danger()->send();

            return;
        }

        if ($type === CreditNote::TYPE_CUSTOMER) {
            Customer::query()
                ->where('company_id', $tenant->id)
                ->whereKey($data['customer_id'])
                ->firstOrFail();
        } else {
            Supplier::query()
                ->where('company_id', $tenant->id)
                ->whereKey($data['supplier_id'])
                ->firstOrFail();
        }

        DB::transaction(function () use ($tenant, $data, $type, $amount): void {
            $payload = [
                'company_id' => $tenant->id,
                'user_id' => Auth::id(),
                'document_date' => $data['document_date'],
                'counterparty_type' => $type,
                'customer_id' => $type === CreditNote::TYPE_CUSTOMER ? (int) $data['customer_id'] : null,
                'supplier_id' => $type === CreditNote::TYPE_SUPPLIER ? (int) $data['supplier_id'] : null,
                'currency_id' => $data['currency_id'] ?? null,
                'account_group_id' => $data['account_group_id'] ?? null,
                'amount' => $amount,
                'description' => $data['description'] ?? null,
            ];

            if (! empty($data['id'])) {
                $note = CreditNote::query()
                    ->where('company_id', $tenant->id)
                    ->findOrFail((int) $data['id']);
                $payload['document_number'] = $note->document_number;
                $note->update($payload);
            } else {
                $payload['document_number'] = (int) ($data['document_number'] ?? CreditNote::nextDocumentNumber($tenant->id));
                $note = CreditNote::query()->create($payload);
            }

            $note->refresh();
            app(CreditNoteLedgerSync::class)->sync($note);
        });

        Notification::make()->title('تم الحفظ')->success()->send();

        $this->redirect(CreditNotePage::getUrl(['tenant' => $tenant]));
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
                ->url(fn (): string => CreditNotePage::getUrl(['tenant' => Filament::getTenant()])),
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
        return 'اشعار دائن';
    }
}
