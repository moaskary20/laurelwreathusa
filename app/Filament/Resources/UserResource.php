<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Support\UserPermissionLabels;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'إدارة';

    protected static ?string $navigationLabel = 'المستخدمين';

    protected static ?string $modelLabel = 'مستخدم';

    protected static ?string $pluralModelLabel = 'المستخدمين';

    protected static ?string $recordTitleAttribute = 'name_ar';

    protected static ?int $navigationSort = 3;

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
                                    ->required()
                                    ->disabled(fn () => $tenant !== null)
                                    ->dehydrated()
                                    ->native(false),
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
                            ]),
                    ]),
                Forms\Components\Select::make('office_id')
                    ->label('المكتب')
                    ->relationship(
                        name: 'office',
                        titleAttribute: 'name_ar',
                        modifyQueryUsing: fn (Builder $query) => $query->when(
                            $tenant,
                            fn (Builder $q) => $q->where('company_id', $tenant->getKey()),
                        ),
                    )
                    ->searchable()
                    ->preload()
                    ->native(false),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Toggle::make('is_main_user')
                            ->label('مستخدم رئيسي')
                            ->inline(false),
                        Forms\Components\Toggle::make('is_super_user')
                            ->label('مستخدم سوبر')
                            ->inline(false),
                    ]),
                Forms\Components\Section::make('صلاحيات التفصيل')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('')
                            ->options(UserPermissionLabels::all())
                            ->columns(4)
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->searchable()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
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
                Tables\Columns\IconColumn::make('is_main_user')
                    ->label('رئيسي')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_super_user')
                    ->label('سوبر')
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
