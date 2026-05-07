<?php

namespace App\Filament\Pages\Payroll;

use App\Filament\Pages\Concerns\InteractsWithFormActions;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Employee;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\Rule as ValidationRule;

final class EmployeeDefinitionFormPage extends Page
{
    use InteractsWithFormActions;

    protected static ?string $slug = 'employee-definition-form';

    protected static string $view = 'filament.pages.payroll.employee-definition-form';

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
            $employee = Employee::query()
                ->where('company_id', $tenant->id)
                ->findOrFail((int) $editId);

            $this->form->fill([
                'id' => $employee->id,
                'name_ar' => $employee->name_ar,
                'name_en' => $employee->name_en,
                'national_id' => $employee->national_id,
                'social_security_number' => $employee->social_security_number,
                'hiring_date' => $employee->hiring_date?->format('Y-m-d'),
                'termination_date' => $employee->termination_date?->format('Y-m-d'),
                'job_number' => $employee->job_number,
                'basic_salary' => $employee->basic_salary,
                'social_security_rate' => $employee->social_security_rate,
                'company_social_security_rate' => $employee->company_social_security_rate,
                'commission_rate' => $employee->commission_rate,
                'marital_status' => $employee->marital_status,
                'phone_allowance' => $employee->phone_allowance,
                'deduction_type' => $employee->deduction_type,
                'cost_center_id' => $employee->cost_center_id,
            ]);
        } else {
            $this->form->fill([
                'basic_salary' => 0,
                'social_security_rate' => 0,
                'company_social_security_rate' => 0,
                'commission_rate' => 0,
                'marital_status' => 'single',
                'phone_allowance' => false,
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
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('name_ar')
                                    ->label('اسم الموظف')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('national_id')
                                    ->label('الرقم الوطني')
                                    ->required()
                                    ->maxLength(50),
                                Forms\Components\DatePicker::make('hiring_date')
                                    ->label('تاريخ التعيين')
                                    ->native(false)
                                    ->displayFormat('Y-m-d')
                                    ->required(),
                                Forms\Components\DatePicker::make('termination_date')
                                    ->label('تاريخ انهاء الخدمات')
                                    ->native(false)
                                    ->displayFormat('Y-m-d'),
                                Forms\Components\TextInput::make('basic_salary')
                                    ->label('الراتب الاساسي')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                                Forms\Components\TextInput::make('company_social_security_rate')
                                    ->label('نسبة الشركة بالضمان الاجتماعي')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.0001),
                            ]),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('name_en')
                                    ->label('اسم الموظف EN')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('social_security_number')
                                    ->label('رقم الضمان')
                                    ->required()
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('commission_rate')
                                    ->label('commission rate')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.0001),
                                Forms\Components\TextInput::make('job_number')
                                    ->label('الرقم الوظيفي')
                                    ->required()
                                    ->maxLength(50)
                                    ->rule(function () use ($tenant) {
                                        $rule = ValidationRule::unique('employees', 'job_number')
                                            ->where('company_id', $tenant->id);
                                        if (! empty($this->data['id'])) {
                                            $rule->ignore($this->data['id']);
                                        }

                                        return $rule;
                                    }),
                                Forms\Components\TextInput::make('social_security_rate')
                                    ->label('نسبة الضمان الاجتماعي')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.0001),
                            ]),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Fieldset::make('نوع العلاوة')
                                    ->schema([
                                        Forms\Components\Checkbox::make('phone_allowance')
                                            ->label('هاتف')
                                            ->inline(false),
                                    ]),
                                Forms\Components\Radio::make('marital_status')
                                    ->label('Marital Status')
                                    ->options([
                                        'single' => 'اعزب',
                                        'married' => 'متزوج',
                                    ])
                                    ->inline()
                                    ->required(),
                            ]),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('deduction_type')
                                    ->label('نوع الاقتطاع')
                                    ->maxLength(255),
                                Forms\Components\Select::make('cost_center_id')
                                    ->label('مراكز التكلفة')
                                    ->options(
                                        CostCenter::query()
                                            ->where('company_id', $tenant->id)
                                            ->orderBy('name_ar')
                                            ->pluck('name_ar', 'id')
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                            ]),
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
        $data = $this->normalizeEmployeeDataForTenant($data, $tenant);

        $payload = [
            'company_id' => $tenant->id,
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'national_id' => $data['national_id'],
            'social_security_number' => $data['social_security_number'],
            'hiring_date' => $data['hiring_date'],
            'termination_date' => $data['termination_date'] ?? null,
            'job_number' => $data['job_number'],
            'basic_salary' => (float) ($data['basic_salary'] ?? 0),
            'social_security_rate' => (float) ($data['social_security_rate'] ?? 0),
            'company_social_security_rate' => (float) ($data['company_social_security_rate'] ?? 0),
            'commission_rate' => (float) ($data['commission_rate'] ?? 0),
            'marital_status' => $data['marital_status'],
            'phone_allowance' => (bool) ($data['phone_allowance'] ?? false),
            'deduction_type' => $data['deduction_type'] ?? null,
            'cost_center_id' => $data['cost_center_id'] ?? null,
        ];

        if (! empty($data['id'])) {
            Employee::query()
                ->where('company_id', $tenant->id)
                ->whereKey((int) $data['id'])
                ->update($payload);
        } else {
            Employee::query()->create($payload);
        }

        Notification::make()->title('تم الحفظ')->success()->send();

        $this->redirect(EmployeeDefinitionPage::getUrl());
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
                ->action(fn () => $this->redirect(EmployeeDefinitionPage::getUrl())),
            Action::make('back')
                ->label('العودة للقائمة الرئيسية')
                ->icon('heroicon-o-x-mark')
                ->url(fn (): string => EmployeeDefinitionPage::getUrl()),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        $editId = request()->query('id');

        return ($editId !== null && $editId !== '') ? 'تعديل موظف' : 'اضافة موظف';
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function normalizeEmployeeDataForTenant(array $data, Company $tenant): array
    {
        $costCenterId = $data['cost_center_id'] ?? null;
        if ($costCenterId === '' || $costCenterId === null) {
            $data['cost_center_id'] = null;

            return $data;
        }

        CostCenter::query()
            ->where('company_id', $tenant->id)
            ->whereKey((int) $costCenterId)
            ->firstOrFail();

        $data['cost_center_id'] = (int) $costCenterId;

        return $data;
    }
}
