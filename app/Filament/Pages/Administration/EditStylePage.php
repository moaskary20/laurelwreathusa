<?php

namespace App\Filament\Pages\Administration;

use App\Support\AdminTheme;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @property Form $form
 */
final class EditStylePage extends Page
{
    protected static ?string $slug = 'edit-style';

    protected static string $view = 'filament.pages.administration.edit-style';

    protected static ?string $navigationGroup = 'إدارة';

    protected static ?string $title = 'تعديل ال style';

    protected static ?string $navigationLabel = 'تعديل ال style';

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?int $navigationSort = 1;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    /**
     * @var array<string, string>
     */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill(AdminTheme::all());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('الألوان الأساسية')
                    ->description('اللون الأساسي للعناوين والتمييز. اللون الثانوي للأزرار والتركيز.')
                    ->schema([
                        Forms\Components\ColorPicker::make('primary')
                            ->label('اللون الأساسي')
                            ->required()
                            ->hex(),
                        Forms\Components\ColorPicker::make('secondary')
                            ->label('اللون الثانوي')
                            ->required()
                            ->hex(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('ألوان النص')
                    ->schema([
                        Forms\Components\ColorPicker::make('text')
                            ->label('لون الخط الرئيسي')
                            ->required()
                            ->hex(),
                        Forms\Components\ColorPicker::make('text_muted')
                            ->label('لون الخط الثانوي')
                            ->required()
                            ->hex(),
                        Forms\Components\ColorPicker::make('input_text')
                            ->label('لون نص حقول الإدخال')
                            ->required()
                            ->hex(),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('القائمة الجانبية')
                    ->schema([
                        Forms\Components\ColorPicker::make('active')
                            ->label('لون العنصر النشط')
                            ->required()
                            ->hex(),
                        Forms\Components\ColorPicker::make('sidebar_background')
                            ->label('خلفية القائمة الجانبية')
                            ->required()
                            ->hex(),
                        Forms\Components\ColorPicker::make('logo_header_background')
                            ->label('خلفية منطقة اللوجو')
                            ->required()
                            ->hex(),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('الخلفيات')
                    ->schema([
                        Forms\Components\ColorPicker::make('background')
                            ->label('خلفية الصفحة')
                            ->required()
                            ->hex(),
                        Forms\Components\ColorPicker::make('card_background')
                            ->label('خلفية البطاقات والأقسام')
                            ->required()
                            ->hex(),
                        Forms\Components\ColorPicker::make('topbar_background')
                            ->label('خلفية الشريط العلوي')
                            ->required()
                            ->hex(),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('حقول الإدخال')
                    ->schema([
                        Forms\Components\ColorPicker::make('input_background')
                            ->label('خلفية حقول الإدخال')
                            ->required()
                            ->hex(),
                        Forms\Components\ColorPicker::make('input_border')
                            ->label('حدود حقول الإدخال')
                            ->required()
                            ->hex(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('الجداول والحدود')
                    ->schema([
                        Forms\Components\ColorPicker::make('border')
                            ->label('لون الحدود العام')
                            ->required()
                            ->hex(),
                        Forms\Components\ColorPicker::make('table_header_background')
                            ->label('خلفية رأس الجدول')
                            ->required()
                            ->hex(),
                        Forms\Components\ColorPicker::make('table_row_hover_background')
                            ->label('خلفية صف الجدول عند التمرير')
                            ->required()
                            ->hex(),
                    ])
                    ->columns(3),
                Forms\Components\Placeholder::make('preview')
                    ->label('ملاحظة')
                    ->content('بعد الحفظ ستُطبَّق الألوان على كل صفحات لوحة التحكم. حدّث الصفحة إن لم تظهر التغييرات فوراً.'),
            ]);
    }

    public function save(): void
    {
        AdminTheme::save($this->form->getState());

        Notification::make()
            ->success()
            ->title('تم حفظ ألوان الواجهة')
            ->body('حدّث الصفحة إن لم تظهر التغييرات فوراً.')
            ->send();

        $this->redirect(self::getUrl());
    }

    public function resetColors(): void
    {
        AdminTheme::resetToDefaults();
        $this->form->fill(AdminTheme::all());

        Notification::make()
            ->success()
            ->title('تمت إعادة الألوان الافتراضية')
            ->send();
    }

    /**
     * @return array<int | string, string | Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->statePath('data'),
            ),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'تعديل ال style';
    }
}
