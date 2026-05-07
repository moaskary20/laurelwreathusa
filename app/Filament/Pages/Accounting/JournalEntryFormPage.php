<?php

namespace App\Filament\Pages\Accounting;

use App\Filament\Pages\Concerns\InteractsWithFormActions;
use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Supplier;
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
use Illuminate\Support\Facades\DB;

final class JournalEntryFormPage extends Page
{
    use InteractsWithFormActions;

    protected static ?string $slug = 'journal-entry-form';

    protected static string $view = 'filament.pages.accounting.journal-entry-form';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'قيود';

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $mainCurrency = Currency::query()
            ->where('company_id', $tenant->id)
            ->where('is_main', true)
            ->first();

        $editId = request()->query('id');
        if ($editId !== null && $editId !== '') {
            $entry = JournalEntry::query()
                ->where('company_id', $tenant->id)
                ->with(['lines'])
                ->findOrFail((int) $editId);

            $this->form->fill($this->entryToFormData($entry));
        } else {
            $this->form->fill([
                'entry_number' => JournalEntry::nextEntryNumber($tenant->id),
                'entry_date' => now()->toDateString(),
                'currency_id' => $mainCurrency?->id,
                'title' => '',
                'lines' => [],
                'totals_debit' => 0,
                'totals_credit' => 0,
                'totals_debit_foreign' => 0,
                'totals_credit_foreign' => 0,
            ]);
        }

        $this->recalculateTotals(sendNotification: false);

        $this->bootedInteractsWithFormActions();
    }

    /**
     * @return array<string, mixed>
     */
    protected function entryToFormData(JournalEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'entry_number' => $entry->entry_number,
            'entry_date' => $entry->entry_date?->format('Y-m-d'),
            'currency_id' => $entry->currency_id,
            'title' => $entry->title,
            'notes' => $entry->notes,
            'lines' => $entry->lines->map(fn (JournalEntryLine $line, int $index): array => [
                'account_group_id' => $line->account_group_id,
                'description' => $line->description,
                'debit' => $line->debit,
                'credit' => $line->credit,
                'debit_foreign' => $line->debit_foreign,
                'credit_foreign' => $line->credit_foreign,
                'customer_id' => $line->customer_id,
                'supplier_id' => $line->supplier_id,
                'sort_order' => $line->sort_order ?: $index,
            ])->values()->all(),
            'totals_debit' => 0,
            'totals_credit' => 0,
            'totals_debit_foreign' => 0,
            'totals_credit_foreign' => 0,
        ];
    }

    public function form(Form $form): Form
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $accountOptions = AccountGroup::indentedPostingOptionsForCompany($tenant->id);
        $mainCurrencyName = Currency::query()
            ->where('company_id', $tenant->id)
            ->where('is_main', true)
            ->value('name_ar') ?? 'محلي';

        return $form
            ->schema([
                Forms\Components\Hidden::make('id'),
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\DatePicker::make('entry_date')
                            ->label('تاريخ القيد')
                            ->required()
                            ->native(false)
                            ->displayFormat('Y/m/d'),
                        Forms\Components\TextInput::make('entry_number')
                            ->label('رقم القيد')
                            ->disabled()
                            ->dehydrated(true),
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
                Forms\Components\TextInput::make('title')
                    ->label('عنوان القيد')
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull(),
                Forms\Components\Actions::make([
                    FormInlineAction::make('calculate')
                        ->label('احتساب')
                        ->icon('heroicon-m-calculator')
                        ->color('gray')
                        ->action(fn () => $this->recalculateTotals(sendNotification: true)),
                ]),
                Forms\Components\Repeater::make('lines')
                    ->label('بنود القيد')
                    ->schema([
                        Forms\Components\Select::make('account_group_id')
                            ->label('رقم الحساب')
                            ->required()
                            ->options($accountOptions)
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Forms\Components\TextInput::make('description')
                            ->label('الوصف')
                            ->maxLength(500),
                        Forms\Components\TextInput::make('debit')
                            ->label('مدين')
                            ->helperText($mainCurrencyName)
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn () => $this->recalculateTotals(sendNotification: false)),
                        Forms\Components\TextInput::make('debit_foreign')
                            ->label('مدين')
                            ->helperText('أجنبي')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn () => $this->recalculateTotals(sendNotification: false)),
                        Forms\Components\TextInput::make('credit')
                            ->label('دائن')
                            ->helperText($mainCurrencyName)
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn () => $this->recalculateTotals(sendNotification: false)),
                        Forms\Components\TextInput::make('credit_foreign')
                            ->label('دائن')
                            ->helperText('أجنبي')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn () => $this->recalculateTotals(sendNotification: false)),
                        Forms\Components\Select::make('customer_id')
                            ->label('عميل')
                            ->placeholder('—')
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
                            ->afterStateUpdated(function ($state, Set $set): void {
                                if ($state) {
                                    $set('supplier_id', null);
                                }
                            }),
                        Forms\Components\Select::make('supplier_id')
                            ->label('مورد')
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
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set): void {
                                if ($state) {
                                    $set('customer_id', null);
                                }
                            }),
                    ])
                    ->columns(4)
                    ->addActionLabel('اضافه +')
                    ->defaultItems(0)
                    ->collapsible(),
                Forms\Components\Grid::make(4)
                    ->schema([
                        Forms\Components\TextInput::make('totals_debit')
                            ->label('مجموع المدين')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('totals_credit')
                            ->label('مجموع الدائن')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('totals_debit_foreign')
                            ->label('مجموع مدين أجنبي')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('totals_credit_foreign')
                            ->label('مجموع دائن أجنبي')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->columns(1)
            ->statePath('data');
    }

    public function recalculateTotals(bool $sendNotification = false): void
    {
        $lines = $this->data['lines'] ?? [];
        $td = $tc = $tdf = $tcf = 0.0;
        foreach ($lines as $line) {
            $td += (float) ($line['debit'] ?? 0);
            $tc += (float) ($line['credit'] ?? 0);
            $tdf += (float) ($line['debit_foreign'] ?? 0);
            $tcf += (float) ($line['credit_foreign'] ?? 0);
        }
        $this->data['totals_debit'] = round($td, 2);
        $this->data['totals_credit'] = round($tc, 2);
        $this->data['totals_debit_foreign'] = round($tdf, 2);
        $this->data['totals_credit_foreign'] = round($tcf, 2);

        if ($sendNotification) {
            $balanced = abs($td - $tc) < 0.005;
            $n = Notification::make()
                ->title($balanced ? 'القيد متوازن' : 'القيد غير متوازن (العملة الرئيسية)');
            if ($balanced) {
                $n->success();
            } else {
                $n->warning();
            }
            $n->send();
        }
    }

    public function save(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $this->form->validate();
        $this->recalculateTotals(sendNotification: false);
        $data = $this->form->getState();

        $lines = collect($data['lines'] ?? [])
            ->filter(fn (array $row): bool => filled($row['account_group_id'] ?? null))
            ->values();

        if ($lines->isEmpty()) {
            Notification::make()->title('أضف بنداً واحداً على الأقل')->danger()->send();

            return;
        }

        $sumDebit = round($lines->sum(fn (array $l): float => (float) ($l['debit'] ?? 0)), 2);
        $sumCredit = round($lines->sum(fn (array $l): float => (float) ($l['credit'] ?? 0)), 2);

        if (abs($sumDebit - $sumCredit) > 0.01) {
            Notification::make()->title('مجموع المدين يجب أن يساوي مجموع الدائن')->danger()->send();

            return;
        }

        foreach ($lines as $i => $line) {
            $d = (float) ($line['debit'] ?? 0);
            $c = (float) ($line['credit'] ?? 0);
            if ($d > 0 && $c > 0) {
                Notification::make()->title('لا يجب أن يحتوي السطر على مدين ودائن معاً في العملة الرئيسية')->danger()->send();

                return;
            }
        }

        foreach ($lines as $line) {
            $gid = (int) $line['account_group_id'];
            try {
                app(ChartOfAccountsService::class)->assertCanPostToAccount($tenant->id, $gid);
            } catch (\Illuminate\Validation\ValidationException $e) {
                Notification::make()
                    ->danger()
                    ->title('حساب غير صالح للترحيل')
                    ->body(($e->errors()['account_group_id'][0] ?? $e->getMessage()))
                    ->send();

                return;
            }

            if (! empty($line['customer_id']) && ! Customer::query()
                ->where('company_id', $tenant->id)
                ->whereKey((int) $line['customer_id'])
                ->exists()) {
                Notification::make()->title('العميل المختار غير مرتبط بهذه الشركة')->danger()->send();

                return;
            }

            if (! empty($line['supplier_id']) && ! Supplier::query()
                ->where('company_id', $tenant->id)
                ->whereKey((int) $line['supplier_id'])
                ->exists()) {
                Notification::make()->title('المورد المختار غير مرتبط بهذه الشركة')->danger()->send();

                return;
            }
        }

        DB::transaction(function () use ($tenant, $data, $lines): void {
            $payload = [
                'company_id' => $tenant->id,
                'user_id' => Auth::id(),
                'entry_date' => $data['entry_date'],
                'currency_id' => $data['currency_id'] ?? null,
                'title' => $data['title'],
                'notes' => $data['notes'] ?? null,
            ];

            if (! empty($data['id'])) {
                $entry = JournalEntry::query()
                    ->where('company_id', $tenant->id)
                    ->findOrFail((int) $data['id']);
                $payload['entry_number'] = $entry->entry_number;
                $entry->update($payload);
                $entry->lines()->delete();
            } else {
                $payload['entry_number'] = (int) ($data['entry_number'] ?? JournalEntry::nextEntryNumber($tenant->id));
                $entry = JournalEntry::query()->create($payload);
            }

            foreach ($lines as $index => $line) {
                $gid = (int) $line['account_group_id'];
                JournalEntryLine::query()->create([
                    'journal_entry_id' => $entry->id,
                    'account_group_id' => $gid,
                    'description' => $line['description'] ?? null,
                    'debit' => (float) ($line['debit'] ?? 0),
                    'credit' => (float) ($line['credit'] ?? 0),
                    'debit_foreign' => (float) ($line['debit_foreign'] ?? 0),
                    'credit_foreign' => (float) ($line['credit_foreign'] ?? 0),
                    'customer_id' => ! empty($line['customer_id']) ? (int) $line['customer_id'] : null,
                    'supplier_id' => ! empty($line['supplier_id']) ? (int) $line['supplier_id'] : null,
                    'sort_order' => $index,
                ]);
            }
        });

        Notification::make()->title('تم الحفظ')->success()->send();

        $this->redirect(JournalEntriesPage::getUrl(['tenant' => $tenant]));
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
                ->url(fn (): string => JournalEntriesPage::getUrl(['tenant' => Filament::getTenant()])),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'قيود';
    }
}
