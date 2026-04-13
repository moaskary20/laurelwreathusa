<?php

namespace App\Filament\Pages\Customers;

use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\Customer;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property Table $table
 */
final class CustomersListPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'customers-list-page';

    protected static string $view = 'filament.pages.customers.customers-list';

    protected static ?string $navigationGroup = 'العملاء';

    protected static ?string $title = 'قائمة العملاء';

    protected static ?string $navigationLabel = 'قائمة العملاء';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 1;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return Customer::query()->where('company_id', $tenant->id);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name_ar')
                ->label('اسم العميل')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('address_ar')
                ->label('العنوان')
                ->searchable()
                ->placeholder('—'),
            Tables\Columns\TextColumn::make('phone')
                ->label('رقم الهاتف')
                ->placeholder('—'),
            Tables\Columns\TextColumn::make('fax')
                ->label('فاكس')
                ->placeholder('—'),
            Tables\Columns\TextColumn::make('email')
                ->label('البريد الالكتروني')
                ->placeholder('—'),
            Tables\Columns\TextColumn::make('payment_method')
                ->label('طريقة التسديد')
                ->formatStateUsing(fn ($state, Customer $record): string => $record->paymentMethodLabel()),
            Tables\Columns\TextColumn::make('credit_limit')
                ->label('سقف الائتمان')
                ->numeric(2),
            Tables\Columns\TextColumn::make('opening_balance')
                ->label('الارصدة الافتتاحية')
                ->numeric(2),
            Tables\Columns\TextColumn::make('balance')
                ->label('الرصيد')
                ->numeric(2)
                ->placeholder('—'),
        ];
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\CreateAction::make()
                ->label('اضافة عميل')
                ->icon('heroicon-o-plus')
                ->model(Customer::class)
                ->modalHeading('إضافة عميل')
                ->modalWidth(MaxWidth::FourExtraLarge)
                ->modalSubmitActionLabel('حفظ')
                ->modalCancelActionLabel('إلغاء')
                ->createAnother(false)
                ->mutateFormDataUsing(function (array $data): array {
                    $tenant = Filament::getTenant();
                    abort_unless($tenant instanceof Company, 404);

                    $data['company_id'] = $tenant->id;
                    if (! isset($data['balance'])) {
                        $data['balance'] = $data['opening_balance'] ?? 0;
                    }

                    return $data;
                })
                ->successNotificationTitle('تمت إضافة العميل')
                ->form(fn (Form $form): Form => $this->customerForm($form)),
            Tables\Actions\Action::make('importCsv')
                ->label('تحميل اكسل')
                ->icon('heroicon-o-plus')
                ->color('warning')
                ->modalHeading('استيراد من ملف CSV')
                ->modalSubmitActionLabel('استيراد')
                ->modalCancelActionLabel('إلغاء')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('ملف CSV')
                        ->disk('public')
                        ->directory('imports')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->importCustomersFromCsv($data['file'] ?? null);
                }),
            Tables\Actions\Action::make('exportCsv')
                ->label('اصدار الى اكسل')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (): StreamedResponse => $this->exportCustomersCsv()),
        ];
    }

    protected function importCustomersFromCsv(?string $path): void
    {
        if (! is_string($path) || $path === '') {
            Notification::make()->danger()->title('لم يُحدد ملف')->send();

            return;
        }

        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $fullPath = Storage::disk('public')->path($path);
        if (! is_readable($fullPath)) {
            Notification::make()->danger()->title('تعذر قراءة الملف')->send();

            return;
        }

        $handle = fopen($fullPath, 'r');
        if ($handle === false) {
            Notification::make()->danger()->title('تعذر فتح الملف')->send();

            return;
        }

        fgetcsv($handle);
        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                continue;
            }
            Customer::query()->create([
                'company_id' => $tenant->id,
                'name_ar' => $row[0] ?? '',
                'name_en' => $row[1] ?? '',
                'address_ar' => $row[2] ?? null,
                'address_en' => $row[3] ?? null,
                'phone' => $row[4] ?? null,
                'fax' => $row[5] ?? null,
                'email' => $row[6] ?? null,
                'sales_tax_number' => $row[7] ?? null,
                'payment_method' => in_array($row[8] ?? 'cash', array_keys(Customer::paymentMethodOptions()), true)
                    ? $row[8]
                    : 'cash',
                'credit_limit' => isset($row[9]) ? (float) $row[9] : 0,
                'opening_balance' => isset($row[10]) ? (float) $row[10] : 0,
                'balance' => isset($row[11]) ? (float) $row[11] : 0,
                'account_group_id' => isset($row[12]) && $row[12] !== '' ? (int) $row[12] : null,
            ]);
            $count++;
        }
        fclose($handle);

        Storage::disk('public')->delete($path);

        Notification::make()
            ->success()
            ->title('تم الاستيراد')
            ->body('عدد السجلات: '.$count)
            ->send();

        $this->resetTable();
    }

    protected function exportCustomersCsv(): StreamedResponse
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $fileName = 'customers-'.$tenant->id.'-'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($tenant): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'name_ar',
                'name_en',
                'address_ar',
                'address_en',
                'phone',
                'fax',
                'email',
                'sales_tax_number',
                'payment_method',
                'credit_limit',
                'opening_balance',
                'balance',
                'account_group_id',
            ]);

            Customer::query()
                ->where('company_id', $tenant->id)
                ->orderBy('id')
                ->cursor()
                ->each(function (Customer $c) use ($out): void {
                    fputcsv($out, [
                        $c->name_ar,
                        $c->name_en,
                        $c->address_ar,
                        $c->address_en,
                        $c->phone,
                        $c->fax,
                        $c->email,
                        $c->sales_tax_number,
                        $c->payment_method,
                        $c->credit_limit,
                        $c->opening_balance,
                        $c->balance,
                        $c->account_group_id,
                    ]);
                });

            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make()
                ->label('')
                ->icon('heroicon-o-clipboard-document-list')
                ->iconButton()
                ->size(ActionSize::Small)
                ->tooltip('عرض')
                ->modalHeading('بيانات العميل')
                ->form(fn (Form $form): Form => $this->customerForm($form)),
            Tables\Actions\EditAction::make()
                ->label('تعديل')
                ->icon('heroicon-o-pencil')
                ->iconButton()
                ->size(ActionSize::Small)
                ->tooltip('تعديل')
                ->modalHeading('تعديل عميل')
                ->modalWidth(MaxWidth::FourExtraLarge)
                ->modalSubmitActionLabel('حفظ')
                ->modalCancelActionLabel('إلغاء')
                ->successNotificationTitle('تم حفظ التعديلات')
                ->form(fn (Form $form): Form => $this->customerForm($form)),
            Tables\Actions\DeleteAction::make()
                ->label('حذف')
                ->icon('heroicon-o-trash')
                ->iconButton()
                ->size(ActionSize::Small)
                ->tooltip('حذف')
                ->modalHeading('حذف العميل')
                ->modalSubmitActionLabel('حذف')
                ->modalCancelActionLabel('إلغاء')
                ->successNotificationTitle('تم الحذف'),
        ];
    }

    protected function getTableActionsColumnLabel(): ?string
    {
        return 'خيارات';
    }

    public function getTableModelLabel(): ?string
    {
        return 'عميل';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'عملاء';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا يوجد عملاء';
    }

    public function customerForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('name_ar')
                                    ->label('اسم العميل')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('address_ar')
                                    ->label('العنوان')
                                    ->maxLength(500),
                                Forms\Components\TextInput::make('phone')
                                    ->label('رقم الهاتف')
                                    ->tel()
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('fax')
                                    ->label('الفاكس')
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('credit_limit')
                                    ->label('سقف الائتمان')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                                Forms\Components\TextInput::make('opening_balance')
                                    ->label('الأرصدة الافتتاحية')
                                    ->numeric()
                                    ->default(0),
                            ]),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('name_en')
                                    ->label('اسم العميل بالإنجليزي')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('address_en')
                                    ->label('العنوان')
                                    ->maxLength(500),
                                Forms\Components\TextInput::make('sales_tax_number')
                                    ->label('رقم ضريبة المبيعات')
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('email')
                                    ->label('الإيميل')
                                    ->email()
                                    ->maxLength(255),
                                Forms\Components\Select::make('payment_method')
                                    ->label('طريقة التسديد')
                                    ->options(Customer::paymentMethodOptions())
                                    ->required()
                                    ->native(false)
                                    ->searchable(),
                                Forms\Components\Select::make('account_group_id')
                                    ->label('المجموعات')
                                    ->options(function (): array {
                                        $tenant = Filament::getTenant();
                                        abort_unless($tenant instanceof Company, 404);

                                        return AccountGroup::indentedOptionsForCompany($tenant->id);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                            ]),
                    ]),
                Forms\Components\TextInput::make('balance')
                    ->label('الرصيد')
                    ->numeric()
                    ->default(0)
                    ->hiddenOn('create')
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }

    public function getTitle(): string|Htmlable
    {
        return 'قائمة العملاء';
    }
}
