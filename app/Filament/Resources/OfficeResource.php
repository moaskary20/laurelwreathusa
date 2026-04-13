<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfficeResource\Pages;
use App\Models\Office;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OfficeResource extends Resource
{
    protected static ?string $model = Office::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?string $navigationGroup = 'إدارة';

    protected static ?string $navigationLabel = 'المكتب';

    protected static ?string $modelLabel = 'مكتب';

    protected static ?string $pluralModelLabel = 'المكاتب';

    protected static ?string $recordTitleAttribute = 'name_ar';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('users');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('الايميل')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('رقم الهاتف')
                    ->tel()
                    ->maxLength(50),
                Forms\Components\TextInput::make('phone_secondary')
                    ->label('رقم هاتف إضافي')
                    ->tel()
                    ->maxLength(50),
                Forms\Components\Textarea::make('address')
                    ->label('العنوان')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('po_box')
                    ->label('ص.ب')
                    ->maxLength(100),
                Forms\Components\FileUpload::make('logo')
                    ->label('اللوجو')
                    ->image()
                    ->disk('public')
                    ->directory('offices/logos')
                    ->imageEditor()
                    ->extraFieldWrapperAttributes(['class' => 'ci-logo-field'])
                    ->columnSpanFull(),
            ])
            ->columns(2);
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
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('email')
                    ->label('الايميل')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('phone_secondary')
                    ->label('رقم هاتف إضافي')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('address')
                    ->label('العنوان')
                    ->limit(40)
                    ->tooltip(fn ($state) => $state)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('po_box')
                    ->label('ص.ب')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('عدد المستخدمين')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\ImageColumn::make('logo')
                    ->label('اللوجو')
                    ->disk('public')
                    ->circular(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOffices::route('/'),
            'create' => Pages\CreateOffice::route('/create'),
            'edit' => Pages\EditOffice::route('/{record}/edit'),
        ];
    }
}
