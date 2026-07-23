<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Company;
use App\Models\User;
use App\Support\UserPermissionRegistry;
use Filament\Facades\Filament;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    /**
     * Keep company_id from the form (system admin may assign any company).
     * Tenant scoping is handled manually in getEloquentQuery().
     */
    protected static bool $isScopedToTenant = false;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'إدارة';

    protected static ?string $navigationLabel = 'المستخدمين';

    protected static ?string $modelLabel = 'مستخدم';

    protected static ?string $pluralModelLabel = 'المستخدمين';

    protected static ?string $recordTitleAttribute = 'name_ar';

    protected static ?int $navigationSort = 3;

    public static function canSelectCompany(): bool
    {
        return auth()->user()?->isSystemAdmin() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $tenant = Filament::getTenant();
        if ($tenant) {
            $query->where('company_id', $tenant->getKey());
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $tenant = Filament::getTenant();

        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('name_ar')
                                    ->label('الاسم بالعربية')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('username')
                                    ->label('اسم المستخدم')
                                    ->required()
                                    ->maxLength(100)
                                    ->unique(ignoreRecord: true),
                                Forms\Components\TextInput::make('email')
                                    ->label('البريد الالكتروني')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                Forms\Components\Select::make('company_id')
                                    ->label('الشركة')
                                    ->relationship('company', 'trade_name')
                                    ->default($tenant?->getKey())
                                    ->required(fn (Get $get): bool => ! (bool) $get('is_system_admin'))
                                    ->disabled(fn (): bool => ! self::canSelectCompany())
                                    ->dehydrated()
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set): void {
                                        $set('office_id', null);
                                        $company = $state ? Company::query()->find($state) : null;
                                        $set('storage_quota_gb', self::storageQuotaGbFromMb($company?->storage_quota_mb));
                                    }),
                            ]),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('name_en')
                                    ->label('الاسم بالانجليزية')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('password')
                                    ->label('كلمة المرور')
                                    ->password()
                                    ->revealable()
                                    ->minLength(8)
                                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? $state : null)
                                    ->dehydrated(fn (?string $state): bool => filled($state))
                                    ->required(fn (): bool => str_contains(request()->url(), '/users/create'))
                                    ->helperText(
                                        fn (): ?string => str_contains(request()->url(), '/users/create')
                                            ? null
                                            : 'اتركه فارغاً إن لم ترد تغيير كلمة المرور'
                                    ),
                                Forms\Components\TextInput::make('phone')
                                    ->label('رقم الهاتف')
                                    ->tel()
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('subscription_validity')
                                    ->label('صلاحية الاشتراك')
                                    ->maxLength(255),
                                self::companyStorageQuotaField($tenant),
                            ]),
                    ]),
                Forms\Components\Select::make('office_id')
                    ->label('المكتب')
                    ->relationship(
                        name: 'office',
                        titleAttribute: 'name_ar',
                        modifyQueryUsing: function (Builder $query, Get $get) use ($tenant): void {
                            $companyId = $get('company_id') ?: $tenant?->getKey();
                            if ($companyId) {
                                $query->where('company_id', $companyId);
                            }
                        },
                    )
                    ->searchable()
                    ->preload()
                    ->native(false),
                Forms\Components\Toggle::make('is_system_admin')
                    ->label('مدير النظام')
                    ->helperText('يمكنه تصفح جميع الشركات داخل النظام')
                    ->inline(false)
                    ->live(),
                ...self::permissionGrantSections(),
            ]);
    }

    /**
     * @return list<Forms\Components\Section>
     */
    public static function permissionGrantSections(): array
    {
        $sections = [];

        foreach (UserPermissionRegistry::grouped() as $groupKey => $block) {
            $sections[] = Forms\Components\Section::make($block['label'])
                ->schema([
                    Forms\Components\CheckboxList::make('permission_grants.'.$groupKey)
                        ->label('')
                        ->options($block['items'])
                        ->columns(3)
                        ->gridDirection('row')
                        ->bulkToggleable()
                        ->searchable()
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->columnSpanFull();
        }

        return $sections;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function persistPermissionGrants(array $data): array
    {
        $data['permissions'] = UserPermissionRegistry::flattenGrants($data['permission_grants'] ?? []);
        unset($data['permission_grants']);

        return $data;
    }

    public static function companyStorageQuotaField(?Company $tenant): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('storage_quota_gb')
            ->label('مساحة التخزين')
            ->numeric()
            ->minValue(0)
            ->step(0.01)
            ->suffix('جيجابايت')
            ->default(fn (): ?float => $tenant ? self::storageQuotaGbFromMb($tenant->storage_quota_mb) : null)
            ->helperText('الحد الأقصى لمساحة تخزين ملفات شركة العميل')
            ->visible(fn (Get $get): bool => filled($get('company_id')))
            ->dehydrated(false);
    }

    public static function storageQuotaGbFromMb(?int $megabytes): ?float
    {
        if ($megabytes === null) {
            return null;
        }

        return round($megabytes / 1024, 2);
    }

    public static function storageQuotaMbFromGb(mixed $gigabytes): ?int
    {
        if ($gigabytes === null || $gigabytes === '') {
            return null;
        }

        return (int) round((float) $gigabytes * 1024);
    }

    public static function persistCompanyStorageQuota(?int $companyId, mixed $storageQuotaGb): void
    {
        if (! $companyId) {
            return;
        }

        Company::query()
            ->whereKey($companyId)
            ->update(['storage_quota_mb' => self::storageQuotaMbFromGb($storageQuotaGb)]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('username')
                    ->label('اسم المستخدم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('الهاتف')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('company.storage_quota_mb')
                    ->label('مساحة التخزين')
                    ->formatStateUsing(fn (?int $state): string => $state === null
                        ? '—'
                        : self::storageQuotaGbFromMb($state).' جيجابايت')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_system_admin')
                    ->label('مدير النظام')
                    ->boolean(),
                Tables\Columns\TextColumn::make('office.name_ar')
                    ->label('المكتب')
                    ->placeholder('—'),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
