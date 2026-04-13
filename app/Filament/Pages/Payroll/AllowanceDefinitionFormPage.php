<?php

namespace App\Filament\Pages\Payroll;

use App\Filament\Pages\Concerns\InteractsWithFormActions;
use App\Models\Company;
use App\Models\PayrollAllowance;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\Rule as ValidationRule;

final class AllowanceDefinitionFormPage extends Page
{
    use InteractsWithFormActions;

    protected static ?string $slug = 'allowance-definition-form';

    protected static string $view = 'filament.pages.payroll.allowance-definition-form';

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
            $allowance = PayrollAllowance::query()
                ->where('company_id', $tenant->id)
                ->findOrFail((int) $editId);

            $this->form->fill([
                'id' => $allowance->id,
                'allowance_type' => $allowance->allowance_type,
                'amount' => $allowance->amount,
                'frequency' => $allowance->frequency,
                'start_date' => $allowance->start_date?->format('Y-m-d'),
            ]);
        } else {
            $this->form->fill([
                'amount' => 0,
                'frequency' => 'monthly',
            ]);
        }

        $this->bootedInteractsWithFormActions();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('id'),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('allowance_type')
                            ->label('نوع العلاوة')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('amount')
                            ->label('قيمه العلاوة')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->minValue(0)
                            ->step(0.01),
                        Forms\Components\Select::make('frequency')
                            ->label('تحسب')
                            ->options(PayrollAllowance::frequencyOptions())
                            ->required()
                            ->searchable()
                            ->rule(ValidationRule::in(array_keys(PayrollAllowance::frequencyOptions())))
                            ->native(false),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ بداية العلاوة')
                            ->native(false)
                            ->displayFormat('Y-m-d')
                            ->required(),
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

        $payload = [
            'company_id' => $tenant->id,
            'allowance_type' => $data['allowance_type'],
            'amount' => (float) $data['amount'],
            'frequency' => $data['frequency'],
            'start_date' => $data['start_date'],
        ];

        if (! empty($data['id'])) {
            PayrollAllowance::query()
                ->where('company_id', $tenant->id)
                ->whereKey((int) $data['id'])
                ->update($payload);
        } else {
            PayrollAllowance::query()->create($payload);
        }

        Notification::make()->title('تم الحفظ')->success()->send();

        $this->redirect(AllowancesDefinitionPage::getUrl());
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
            Action::make('cancel')
                ->label('إلغاء')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->action(fn () => $this->redirect(AllowancesDefinitionPage::getUrl())),
            Action::make('back')
                ->label('العودة للقائمة الرئيسية')
                ->icon('heroicon-o-x-mark')
                ->url(fn (): string => AllowancesDefinitionPage::getUrl()),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        $editId = request()->query('id');

        return ($editId !== null && $editId !== '') ? 'تعديل علاوة' : 'اضافة علاوة';
    }
}
