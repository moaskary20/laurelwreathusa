<?php

namespace App\Filament\Pages\Suppliers;

use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\Supplier;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property Table $table
 */
final class SuppliersListPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'suppliers-list-page';

    protected static string $view = 'filament.pages.suppliers.suppliers-list';

    protected static ?string $navigationGroup = 'الموردين';

    protected static ?string $title = 'قائمة الموردين';

    protected static ?string $navigationLabel = 'قائمة الموردين';

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 1;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return Supplier::query()
            ->where('company_id', $tenant->id)
            ->with('accountGroup');
    }

    protected function getTableDescription(): string|Htmlable|null
    {
        $tenant = Filament::getTenant();
        if (! $tenant instanceof Company) {
            return null;
        }

        $sum = (float) Supplier::query()
            ->where('company_id', $tenant->id)
            ->sum('balance');

        return 'الرصيد = '.number_format($sum, 2);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name_ar')
                ->label('اسم المورد بالعربي')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('sales_tax_number')
                ->label('الرقم الضريبي')
                ->searchable()
                ->placeholder('—'),
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
            Tables\Columns\TextColumn::make('accountGroup.name_ar')
                ->label('المجموعة')
                ->placeholder('—')
                ->searchable(),
            Tables\Columns\TextColumn::make('opening_balance')
                ->label('الارصدة الافتتاحية')
                ->numeric(2),
            Tables\Columns\TextColumn::make('balance')
                ->label('الرصيد')
                ->numeric(2)
                ->placeholder('—'),
            IconColumn::make('id')
                ->label('كشف حساب')
                ->alignCenter()
                ->icon('heroicon-o-clipboard-document-list')
                ->url(fn (Supplier $record): string => SuppliersAccountStatementPage::getUrl().'?supplier_id='.$record->getKey()),
        ];
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\CreateAction::make()
                ->label('اضافه الموردين')
                ->icon('heroicon-o-plus')
                ->model(Supplier::class)
                ->modalHeading('اضافه الموردين')
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

                    return $this->normalizeSupplierDataForTenant($data, $tenant);
                })
                ->successNotificationTitle('تمت إضافة المورد')
                ->form(fn (Form $form): Form => $this->supplierForm($form)),
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
                    $this->importSuppliersFromCsv($data['file'] ?? null);
                }),
            Tables\Actions\Action::make('exportCsv')
                ->label('اصدار الى اكسل')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (): StreamedResponse => $this->exportSuppliersCsv()),
        ];
    }

    protected function importSuppliersFromCsv(?string $path): void
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
        $invalidGroups = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                continue;
            }

            $accountGroupId = isset($row[12]) && $row[12] !== '' ? (int) $row[12] : null;
            if ($accountGroupId !== null && ! $this->isValidSupplierAccountGroup($accountGroupId, $tenant)) {
                $accountGroupId = null;
                $invalidGroups++;
            }

            Supplier::query()->create([
                'company_id' => $tenant->id,
                'name_ar' => $row[0] ?? '',
                'name_en' => $row[1] ?? '',
                'address_ar' => $row[2] ?? null,
                'address_en' => $row[3] ?? null,
                'phone' => $row[4] ?? null,
                'fax' => $row[5] ?? null,
                'email' => $row[6] ?? null,
                'sales_tax_number' => $row[7] ?? null,
                'payment_method' => in_array($row[8] ?? 'cash', array_keys(Supplier::paymentMethodOptions()), true)
                    ? $row[8]
                    : 'cash',
                'credit_limit' => isset($row[9]) ? (float) $row[9] : 0,
                'opening_balance' => isset($row[10]) ? (float) $row[10] : 0,
                'balance' => isset($row[11]) ? (float) $row[11] : 0,
                'account_group_id' => $accountGroupId,
            ]);
            $count++;
        }
        fclose($handle);

        Storage::disk('public')->delete($path);

        Notification::make()
            ->success()
            ->title('تم الاستيراد')
            ->body('عدد السجلات: '.$count.($invalidGroups > 0 ? '، وتم تجاهل '.$invalidGroups.' مجموعة غير صالحة' : ''))
            ->send();

        $this->resetTable();
    }

    protected function exportSuppliersCsv(): StreamedResponse
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $fileName = 'suppliers-'.$tenant->id.'-'.now()->format('Y-m-d_His').'.csv';

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

            Supplier::query()
                ->where('company_id', $tenant->id)
                ->orderBy('id')
                ->cursor()
                ->each(function (Supplier $s) use ($out): void {
                    fputcsv($out, [
                        $s->name_ar,
                        $s->name_en,
                        $s->address_ar,
                        $s->address_en,
                        $s->phone,
                        $s->fax,
                        $s->email,
                        $s->sales_tax_number,
                        $s->payment_method,
                        $s->credit_limit,
                        $s->opening_balance,
                        $s->balance,
                        $s->account_group_id,
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
            Tables\Actions\EditAction::make()
                ->label('تعديل')
                ->icon('heroicon-o-pencil')
                ->iconButton()
                ->size(ActionSize::Small)
                ->tooltip('تعديل')
                ->modalHeading('تعديل مورد')
                ->modalWidth(MaxWidth::FourExtraLarge)
                ->modalSubmitActionLabel('حفظ')
                ->modalCancelActionLabel('إلغاء')
                ->mutateFormDataUsing(function (array $data): array {
                    $tenant = Filament::getTenant();
                    abort_unless($tenant instanceof Company, 404);

                    return $this->normalizeSupplierDataForTenant($data, $tenant);
                })
                ->successNotificationTitle('تم حفظ التعديلات')
                ->form(fn (Form $form): Form => $this->supplierForm($form)),
            Tables\Actions\DeleteAction::make()
                ->label('حذف')
                ->icon('heroicon-o-trash')
                ->iconButton()
                ->size(ActionSize::Small)
                ->tooltip('حذف')
                ->modalHeading('حذف المورد')
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
        return 'مورد';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'موردين';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا يوجد موردين';
    }

    public function supplierForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('name_ar')
                                    ->label('اسم المورد بالعربي')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('address_ar')
                                    ->label('العنوان')
                                    ->maxLength(500),
                                Forms\Components\TextInput::make('sales_tax_number')
                                    ->label('رقم ضريبة المبيعات')
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('email')
                                    ->label('الايميل')
                                    ->email()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('opening_balance')
                                    ->label('الارصدة الافتتاحية')
                                    ->numeric()
                                    ->default(0),
                            ]),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('name_en')
                                    ->label('اسم المورد بالانجليزي')
                                    ->required()
                                    ->maxLength(255),
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
                                Forms\Components\Select::make('payment_method')
                                    ->label('طريقة التسديد')
                                    ->options(Supplier::paymentMethodOptions())
                                    ->required()
                                    ->native(false)
                                    ->searchable(),
                                Forms\Components\Select::make('account_group_id')
                                    ->label('المجموعات')
                                    ->options(function (): array {
                                        $tenant = Filament::getTenant();
                                        abort_unless($tenant instanceof Company, 404);

                                        return AccountGroup::indentedPostingOptionsForCompany($tenant->id);
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
        return 'قائمة الموردين';
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected function normalizeSupplierDataForTenant(array $data, Company $tenant): array
    {
        $accountGroupId = $data['account_group_id'] ?? null;

        if ($accountGroupId === '' || $accountGroupId === null) {
            $data['account_group_id'] = null;

            return $data;
        }

        $accountGroupId = (int) $accountGroupId;
        if (! $this->isValidSupplierAccountGroup($accountGroupId, $tenant)) {
            throw ValidationException::withMessages([
                'account_group_id' => 'المجموعة المختارة غير مرتبطة بهذه الشركة أو غير مفعلة للترحيل.',
            ]);
        }

        $data['account_group_id'] = $accountGroupId;

        return $data;
    }

    protected function isValidSupplierAccountGroup(int $accountGroupId, Company $tenant): bool
    {
        return AccountGroup::query()
            ->whereKey($accountGroupId)
            ->where('company_id', $tenant->id)
            ->where('is_active', true)
            ->where('is_postable', true)
            ->exists();
    }
}
