<?php

namespace App\Filament\Pages\Customers;

use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\ServiceProduct;
use App\Models\Tax;
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
use Illuminate\Validation\Rules\Unique;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property Table $table
 */
final class ServicesAndProductsListPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'services-and-products-list-page';

    protected static string $view = 'filament.pages.customers.services-and-products-list';

    protected static ?string $navigationGroup = 'العملاء';

    protected static ?string $title = 'قائمة الخدمات و المنتجات';

    protected static ?string $navigationLabel = 'قائمة الخدمات و المنتجات';

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 4;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return ServiceProduct::query()->where('company_id', $tenant->id);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('kind')
                ->label('النوع')
                ->formatStateUsing(fn ($state, ServiceProduct $record): string => $record->kindLabel()),
            Tables\Columns\TextColumn::make('name_ar')
                ->label('اسم الخدمة')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('code')
                ->label('رمز الخدمة')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('sale_price')
                ->label('سعر البيع')
                ->numeric(2),
            Tables\Columns\TextColumn::make('accountGroup.name_ar')
                ->label('المجموعات')
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
                ->label('قائمة الخدمات +')
                ->icon('heroicon-o-plus')
                ->model(ServiceProduct::class)
                ->modalHeading('اضافه خدمة')
                ->modalWidth(MaxWidth::FourExtraLarge)
                ->modalSubmitActionLabel('حفظ')
                ->modalCancelActionLabel('إلغاء')
                ->createAnother(false)
                ->mutateFormDataUsing(function (array $data): array {
                    $tenant = Filament::getTenant();
                    abort_unless($tenant instanceof Company, 404);

                    $data['company_id'] = $tenant->id;

                    return $data;
                })
                ->successNotificationTitle('تمت الإضافة')
                ->form(fn (Form $form): Form => $this->serviceProductForm($form)),
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
                    $this->importServiceProductsFromCsv($data['file'] ?? null);
                }),
            Tables\Actions\Action::make('exportCsv')
                ->label('اصدار الى اكسل')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (): StreamedResponse => $this->exportServiceProductsCsv()),
        ];
    }

    protected function importServiceProductsFromCsv(?string $path): void
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
            if (count($row) < 4) {
                continue;
            }
            $kind = strtolower((string) ($row[4] ?? 'service'));
            if (! in_array($kind, array_keys(ServiceProduct::kindOptions()), true)) {
                $kind = 'service';
            }
            ServiceProduct::query()->updateOrCreate(
                [
                    'company_id' => $tenant->id,
                    'code' => (string) ($row[2] ?? ''),
                ],
                [
                    'name_ar' => $row[0] ?? '',
                    'name_en' => $row[1] ?? null,
                    'sale_price' => isset($row[3]) ? (float) $row[3] : 0,
                    'kind' => $kind,
                    'account_group_id' => isset($row[5]) && $row[5] !== '' ? (int) $row[5] : null,
                    'cost_center_id' => isset($row[6]) && $row[6] !== '' ? (int) $row[6] : null,
                    'tax_id' => isset($row[7]) && $row[7] !== '' ? (int) $row[7] : null,
                ]
            );
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

    protected function exportServiceProductsCsv(): StreamedResponse
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $fileName = 'services-products-'.$tenant->id.'-'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($tenant): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'name_ar',
                'name_en',
                'code',
                'sale_price',
                'kind',
                'account_group_id',
                'cost_center_id',
                'tax_id',
            ]);

            ServiceProduct::query()
                ->where('company_id', $tenant->id)
                ->orderBy('id')
                ->cursor()
                ->each(function (ServiceProduct $r) use ($out): void {
                    fputcsv($out, [
                        $r->name_ar,
                        $r->name_en,
                        $r->code,
                        $r->sale_price,
                        $r->kind,
                        $r->account_group_id,
                        $r->cost_center_id,
                        $r->tax_id,
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
                ->modalHeading('تعديل')
                ->modalWidth(MaxWidth::FourExtraLarge)
                ->modalSubmitActionLabel('حفظ')
                ->modalCancelActionLabel('إلغاء')
                ->successNotificationTitle('تم حفظ التعديلات')
                ->form(fn (Form $form): Form => $this->serviceProductForm($form)),
            Tables\Actions\DeleteAction::make()
                ->label('حذف')
                ->icon('heroicon-o-trash')
                ->iconButton()
                ->size(ActionSize::Small)
                ->tooltip('حذف')
                ->modalHeading('حذف')
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
        return 'خدمة / منتج';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'خدمات ومنتجات';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا توجد بيانات';
    }

    public function serviceProductForm(Form $form): Form
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        return $form
            ->schema([
                Forms\Components\Select::make('kind')
                    ->label('النوع')
                    ->options(ServiceProduct::kindOptions())
                    ->default('service')
                    ->required()
                    ->native(false),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('name_ar')
                                    ->label('اسم الخدمة')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('code')
                                    ->label('رمز الخدمة')
                                    ->required()
                                    ->maxLength(100)
                                    ->unique(
                                        table: 'service_products',
                                        column: 'code',
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('company_id', $tenant->id),
                                    ),
                                Forms\Components\Select::make('account_group_id')
                                    ->label('المجموعات')
                                    ->options(AccountGroup::indentedPostingOptionsForCompany($tenant->id))
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                                Forms\Components\Select::make('cost_center_id')
                                    ->label('مراكز التكلفة')
                                    ->options(function () use ($tenant): array {
                                        return CostCenter::query()
                                            ->where('company_id', $tenant->id)
                                            ->orderBy('name_ar')
                                            ->pluck('name_ar', 'id')
                                            ->all();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                            ]),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('name_en')
                                    ->label('اسم الخدمة بالانجليزي')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('sale_price')
                                    ->label('سعر البيع')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                                Forms\Components\Select::make('tax_id')
                                    ->label('نسبة الضريبة')
                                    ->options(function () use ($tenant): array {
                                        return Tax::query()
                                            ->where('company_id', $tenant->id)
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(fn (Tax $t): array => [
                                                $t->id => $t->name.' ('.$t->rate.'%)',
                                            ])
                                            ->all();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public function getTitle(): string|Htmlable
    {
        return 'قائمة الخدمات و المنتجات';
    }
}
