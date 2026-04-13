<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankAccountResource\Pages;
use App\Models\BankAccount;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'إدارة';

    protected static ?string $navigationLabel = 'بيانات البنك';

    protected static ?string $modelLabel = 'حساب بنكي';

    protected static ?string $pluralModelLabel = 'الحسابات البنكية';

    protected static ?string $recordTitleAttribute = 'name_ar';

    protected static ?int $navigationSort = 4;

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
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('name_ar')
                            ->label('اسم البنك بالعربي')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name_en')
                            ->label('اسم البنك بالانجليزي')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('branch_ar')
                            ->label('الفرع')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('branch_en')
                            ->label('الفرع بالانجليزي')
                            ->maxLength(255),
                        Forms\Components\Placeholder::make('beneficiary')
                            ->label('المستفيد من الحساب')
                            ->content(fn (): string => Filament::getTenant()?->trade_name ?? '—'),
                        Forms\Components\TextInput::make('swift_code')
                            ->label('سويفت كود')
                            ->required()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('iban')
                            ->label('IBAN')
                            ->required()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('account_number')
                            ->label('رقم الحساب')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('nickname')
                            ->label('nickname')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['default' => 2, 'lg' => 1]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('اسم البنك')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch_ar')
                    ->label('الفرع')
                    ->searchable(),
                Tables\Columns\TextColumn::make('iban')
                    ->label('IBAN')
                    ->searchable(),
                Tables\Columns\TextColumn::make('account_number')
                    ->label('رقم الحساب')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nickname')
                    ->label('nickname')
                    ->searchable(),
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
            'index' => Pages\ListBankAccounts::route('/'),
            'create' => Pages\CreateBankAccount::route('/create'),
            'edit' => Pages\EditBankAccount::route('/{record}/edit'),
        ];
    }
}
