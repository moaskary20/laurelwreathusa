<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static bool $isScopedToTenant = false;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'قائمة الشركات';

    protected static ?string $modelLabel = 'شركة';

    protected static ?string $pluralModelLabel = 'الشركات';

    protected static ?string $recordTitleAttribute = 'trade_name';

    protected static ?int $navigationSort = -1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                                Forms\Components\Textarea::make('objectives')
                                    ->label('غايات الشركة')
                                    ->required()
                                    ->rows(3),
                                Forms\Components\TextInput::make('phone')
                                    ->label('الرقم الهاتف')
                                    ->tel()
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('sales_tax_number')
                                    ->label('رقم ضريبة المبيعات')
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('po_box')
                                    ->label('صندوق البريد')
                                    ->maxLength(100),
                                Forms\Components\DatePicker::make('fiscal_year_end')
                                    ->label('تاريخ اغلاق السنة المالية')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y'),
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
                Forms\Components\FileUpload::make('logo')
                    ->label('اللوجو')
                    ->image()
                    ->disk('public')
                    ->directory('companies/logos')
                    ->imageEditor()
                    ->extraFieldWrapperAttributes(['class' => 'ci-logo-field'])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('trade_name')
                    ->label('الاسم التجاري')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('legal_name')
                    ->label('الاسم القانوني')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('national_number')
                    ->label('الرقم الوطني')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('الهاتف')
                    ->placeholder('—'),
                Tables\Columns\ImageColumn::make('logo')
                    ->label('اللوجو')
                    ->disk('public')
                    ->circular(),
            ])
            ->defaultSort('id', 'asc')
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
