<?php

namespace App\Filament\Pages\Administration;

use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
/**
 * @property Form $form
 */
final class CompanyInformationPage extends Page
{
    protected static ?string $slug = 'company-information';

    protected static string $view = 'filament.pages.administration.company-information';

    protected static ?string $navigationGroup = 'إدارة';

    protected static ?string $title = 'معلومات الشركة';

    protected static ?string $navigationLabel = 'معلومات الشركة';

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 2;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    protected ?Company $tenant = null;

    public int $officesCount = 0;

    public int $usersCount = 0;

    public function mount(): void
    {
        $this->tenant = Filament::getTenant();

        abort_unless($this->tenant instanceof Company, 404);

        $this->tenant->load('offices');

        $this->officesCount = $this->tenant->offices()->count();
        $this->usersCount = User::query()
            ->whereHas('office', fn ($q) => $q->where('company_id', $this->tenant->id))
            ->count();

        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $data = $this->tenant->attributesToArray();
        $data['branches'] = $this->tenant->branches ?? [];
        $data['partners'] = $this->tenant->partners ?? [];

        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return $form->schema($this->getCompanyFormSchema());
    }

    /**
     * @return array<Forms\Components\Component>
     */
    protected function getCompanyFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\TextInput::make('legal_name')
                                ->label('اسم الشركة القانوني')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Select::make('legal_type')
                                ->label('النوع القانوني')
                                ->options([
                                    'individual' => 'مؤسسة فردية',
                                    'company' => 'شركة',
                                    'other' => 'أخرى',
                                ])
                                ->required()
                                ->native(false),
                            Forms\Components\TextInput::make('national_number')
                                ->label('رقم الشركة الوطني')
                                ->required()
                                ->maxLength(100),
                            Forms\Components\TextInput::make('sales_invoice_start')
                                ->label('بداية ترقيم فاتورة المبيعات')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required(),
                            Forms\Components\TextInput::make('email')
                                ->label('الايميل')
                                ->email()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('tax_number')
                                ->label('الرقم الضريبي')
                                ->required()
                                ->maxLength(100),
                            Forms\Components\Textarea::make('address')
                                ->label('العنوان')
                                ->rows(3),
                            Forms\Components\TextInput::make('fax')
                                ->label('الفاكس')
                                ->maxLength(50),
                            Forms\Components\Select::make('inventory_system')
                                ->label('نظام الجرد')
                                ->options([
                                    'perpetual' => 'جرد دائم',
                                    'periodic' => 'جرد دوري',
                                ])
                                ->required()
                                ->native(false),
                        ]),
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\TextInput::make('trade_name')
                                ->label('اسم الشركة التجاري')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Select::make('trade_category')
                                ->label('تصنيف النشاط')
                                ->options([
                                    'commercial' => 'تجارية',
                                    'industrial' => 'صناعية',
                                    'service' => 'خدمية',
                                ])
                                ->required()
                                ->native(false),
                            Forms\Components\TextInput::make('registration_number')
                                ->label('رقم التسجيل')
                                ->required()
                                ->maxLength(100),
                            Forms\Components\TextInput::make('legal_name_en')
                                ->label('الاسم الشركة القانوني بالانجليزي')
                                ->maxLength(255),
                            Forms\Components\Textarea::make('objectives')
                                ->label('غايات الشركة')
                                ->required()
                                ->rows(3),
                            Forms\Components\TextInput::make('phone')
                                ->label('رقم الهاتف')
                                ->tel()
                                ->maxLength(50),
                            Forms\Components\TextInput::make('sales_tax_number')
                                ->label('رقم ضريبة المبيعات')
                                ->maxLength(100),
                            Forms\Components\TextInput::make('po_box')
                                ->label('صندوق البريد')
                                ->maxLength(100),
                            Forms\Components\TextInput::make('address_en')
                                ->label('اسم العنوان بالانجليزي')
                                ->maxLength(255),
                            Forms\Components\DatePicker::make('fiscal_year_end')
                                ->label('تاريخ اغلاق السنة المالية')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y'),
                            Forms\Components\TextInput::make('commercial_registry_issuer')
                                ->label('جهة اصدار السجل التجاري')
                                ->maxLength(255),
                            Forms\Components\Select::make('inventory_pricing')
                                ->label('تسعير المخزون')
                                ->options([
                                    'average' => 'متوسط التكلفة',
                                    'fifo' => 'الوارد أولاً صادراً أولاً',
                                    'standard' => 'التكلفة المعيارية',
                                ])
                                ->required()
                                ->native(false),
                        ]),
                ]),
            Forms\Components\Section::make('الفرع')
                ->schema([
                    Forms\Components\Repeater::make('branches')
                        ->label('')
                        ->addActionLabel('اضافه سطر +')
                        ->default([])
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('الفرع')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('address')
                                ->label('عنوان الفرع')
                                ->maxLength(500),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ])
                ->collapsible(),
            Forms\Components\Section::make('الشركاء')
                ->schema([
                    Forms\Components\Repeater::make('partners')
                        ->label('')
                        ->addActionLabel('اضافه سطر +')
                        ->default([])
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('اسماء الشركاء')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('share')
                                ->label('حصصهم')
                                ->maxLength(100),
                            Forms\Components\TextInput::make('type')
                                ->label('نوع الشريك')
                                ->maxLength(100),
                            Forms\Components\TextInput::make('address')
                                ->label('عنوانه')
                                ->maxLength(500),
                            Forms\Components\TextInput::make('email')
                                ->label('الايميل')
                                ->email()
                                ->maxLength(255),
                        ])
                        ->columns([
                            'default' => 1,
                            'lg' => 5,
                        ])
                        ->columnSpanFull(),
                ])
                ->collapsible(),
            Forms\Components\FileUpload::make('logo')
                ->label('logo')
                ->image()
                ->disk('public')
                ->directory('companies/logos')
                ->imageEditor()
                ->extraFieldWrapperAttributes(['class' => 'ci-logo-field'])
                ->columnSpanFull(),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->tenant->update($data);

        $this->tenant->refresh();
        $this->tenant->load('offices');

        $this->officesCount = $this->tenant->offices()->count();
        $this->usersCount = User::query()
            ->whereHas('office', fn ($q) => $q->where('company_id', $this->tenant->id))
            ->count();

        $this->fillForm();

        Notification::make()
            ->success()
            ->title('تم حفظ بيانات الشركة')
            ->send();
    }

    public function cancel(): void
    {
        $this->redirect(Filament::getUrl());
    }

    /**
     * @return array<int | string, string | Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->operation('edit')
                    ->model($this->tenant)
                    ->statePath('data'),
            ),
        ];
    }

    public function getTitle(): string | Htmlable
    {
        return 'معلومات الشركة';
    }
}
