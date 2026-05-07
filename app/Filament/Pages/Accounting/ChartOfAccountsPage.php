<?php

namespace App\Filament\Pages\Accounting;

use App\Models\AccountGroup;
use App\Models\Company;
use App\Services\Accounting\ChartOfAccountsService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * @property Table $table
 */
final class ChartOfAccountsPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationGroup = 'المحاسبة';

    protected static ?string $title = 'شجرة الحسابات';

    protected static ?string $navigationLabel = 'شجرة الحسابات';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.accounting.chart-of-accounts';

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createRoot')
                ->label('إضافة حساب رئيسي')
                ->icon('heroicon-o-plus')
                ->form($this->accountFormSchema())
                ->action(function (array $data): void {
                    $tenant = Filament::getTenant();
                    abort_unless($tenant instanceof Company, 404);

                    $data['parent_id'] = null;
                    try {
                        app(ChartOfAccountsService::class)->createAccount($tenant->id, $data);
                        Notification::make()->success()->title('تمت الإضافة')->send();
                        $this->resetTable();
                    } catch (ValidationException $e) {
                        Notification::make()->danger()->title('تعذر الإضافة')->body($e->getMessage())->send();
                    }
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        return AccountGroup::query()
            ->where('company_id', $tenant->id)
            ->orderBy('code')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('الكود')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('اسم الحساب')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function (?string $state, AccountGroup $record): string {
                        $indent = $record->level > 0 ? str_repeat('— ', (int) $record->level) : '';

                        return $indent.(string) $state;
                    }),
                Tables\Columns\TextColumn::make('account_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'asset' => 'موجودات',
                        'liability' => 'مطلوبات',
                        'equity' => 'حقوق ملكية',
                        'revenue' => 'إيرادات',
                        'expense' => 'مصروفات',
                        default => (string) $state,
                    }),
                Tables\Columns\IconColumn::make('is_postable')
                    ->label('طرفي')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('مفعل')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('account_type')
                    ->label('النوع')
                    ->options([
                        'asset' => 'موجودات',
                        'liability' => 'مطلوبات',
                        'equity' => 'حقوق ملكية',
                        'revenue' => 'إيرادات',
                        'expense' => 'مصروفات',
                    ]),
                Tables\Filters\TernaryFilter::make('is_postable')
                    ->label('طرفي'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('مفعل'),
            ])
            ->actions([
                Tables\Actions\Action::make('addChild')
                    ->label('إضافة فرع')
                    ->icon('heroicon-o-plus')
                    ->form($this->accountFormSchema())
                    ->action(function (AccountGroup $record, array $data): void {
                        $tenant = Filament::getTenant();
                        abort_unless($tenant instanceof Company, 404);

                        $data['parent_id'] = $record->id;

                        try {
                            app(ChartOfAccountsService::class)->createAccount($tenant->id, $data);
                            Notification::make()->success()->title('تمت الإضافة')->send();
                            $this->resetTable();
                        } catch (ValidationException $e) {
                            Notification::make()->danger()->title('تعذر الإضافة')->body($e->getMessage())->send();
                        }
                    }),
                Tables\Actions\Action::make('edit')
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil')
                    ->fillForm(function (AccountGroup $record): array {
                        return [
                            'parent_id' => $record->parent_id,
                            'code' => $record->code,
                            'name_ar' => $record->name_ar,
                            'account_type' => $record->account_type,
                            'normal_balance' => $record->normal_balance,
                            'is_postable' => $record->is_postable,
                            'is_active' => $record->is_active,
                            'allow_manual_entries' => $record->allow_manual_entries,
                            'sort_order' => $record->sort_order,
                        ];
                    })
                    ->form($this->accountFormSchema(includeParent: true))
                    ->action(function (AccountGroup $record, array $data): void {
                        try {
                            app(ChartOfAccountsService::class)->updateAccount($record, $data);
                            Notification::make()->success()->title('تم التعديل')->send();
                            $this->resetTable();
                        } catch (ValidationException $e) {
                            Notification::make()->danger()->title('تعذر التعديل')->body($e->getMessage())->send();
                        }
                    }),
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (AccountGroup $record): string => $record->is_active ? 'إيقاف' : 'تفعيل')
                    ->icon(fn (AccountGroup $record): string => $record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn (AccountGroup $record): string => $record->is_active ? 'warning' : 'success')
                    ->action(function (AccountGroup $record): void {
                        $svc = app(ChartOfAccountsService::class);
                        $record->is_active ? $svc->deactivate($record) : $svc->activate($record);
                        $this->resetTable();
                    }),
                Tables\Actions\Action::make('delete')
                    ->label('حذف')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (AccountGroup $record): void {
                        try {
                            app(ChartOfAccountsService::class)->deleteAccount($record);
                            Notification::make()->success()->title('تم الحذف')->send();
                            $this->resetTable();
                        } catch (ValidationException $e) {
                            Notification::make()->danger()->title('تعذر الحذف')->body($e->getMessage())->send();
                        }
                    }),
            ])
            ->paginated(false);
    }

    /**
     * @return array<int, Forms\Components\Component>
     */
    private function accountFormSchema(bool $includeParent = false): array
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $schema = [];

        if ($includeParent) {
            $schema[] = Forms\Components\Select::make('parent_id')
                ->label('الحساب الأب')
                ->placeholder('—')
                ->options(AccountGroup::indentedAllOptionsForCompany($tenant->id))
                ->searchable()
                ->preload()
                ->native(false);
        }

        return array_merge($schema, [
            Forms\Components\TextInput::make('code')
                ->label('الكود')
                ->required()
                ->maxLength(20),
            Forms\Components\TextInput::make('name_ar')
                ->label('اسم الحساب')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('account_type')
                ->label('النوع')
                ->required()
                ->options([
                    'asset' => 'موجودات',
                    'liability' => 'مطلوبات',
                    'equity' => 'حقوق ملكية',
                    'revenue' => 'إيرادات',
                    'expense' => 'مصروفات',
                ])
                ->native(false),
            Forms\Components\Select::make('normal_balance')
                ->label('الطبيعة')
                ->required()
                ->options([
                    'debit' => 'مدين',
                    'credit' => 'دائن',
                ])
                ->native(false),
            Forms\Components\Toggle::make('is_postable')
                ->label('طرفي (قابل للترحيل)')
                ->default(true),
            Forms\Components\Toggle::make('is_active')
                ->label('مفعل')
                ->default(true),
            Forms\Components\Toggle::make('allow_manual_entries')
                ->label('يسمح بالترحيل اليدوي')
                ->default(true),
            Forms\Components\TextInput::make('sort_order')
                ->label('الترتيب')
                ->numeric()
                ->default(0),
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'شجرة الحسابات';
    }
}
