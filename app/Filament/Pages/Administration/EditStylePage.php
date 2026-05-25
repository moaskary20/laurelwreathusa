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
                Forms\Components\Section::make('ألوان لوحة التحكم')
                    ->description('اللون الأساسي يُستخدم في القائمة الجانبية والعناوين. اللون الثانوي يُستخدم في الأزرار والتمييز.')
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
                Forms\Components\Placeholder::make('preview')
                    ->label('معاينة')
                    ->content(fn (): string => 'بعد الحفظ ستُطبَّق الألوان على كل صفحات لوحة التحكم.'),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        AdminTheme::save([
            'primary' => $data['primary'],
            'secondary' => $data['secondary'],
        ]);

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
