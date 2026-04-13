<?php

namespace App\Filament\Pages\Administration;

use App\Models\Company;
use App\Models\CompanyDocument;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

/**
 * @property \Filament\Tables\Table $table
 */
final class DownloadPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'download';

    protected static string $view = 'filament.pages.administration.download';

    protected static ?string $navigationGroup = 'إدارة';

    protected static ?string $title = 'تحميل';

    protected static ?string $navigationLabel = 'تحميل';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?int $navigationSort = 9;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return CompanyDocument::query()->where('company_id', $tenant->id);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label('اسم المستند')
                ->searchable()
                ->sortable()
                ->alignCenter(),
        ];
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\CreateAction::make()
                ->label('Upload')
                ->icon('heroicon-o-plus')
                ->color('warning')
                ->model(CompanyDocument::class)
                ->modalHeading('رفع مستند')
                ->modalWidth(MaxWidth::Large)
                ->modalSubmitActionLabel('حفظ')
                ->modalCancelActionLabel('إلغاء')
                ->createAnother(false)
                ->mutateFormDataUsing(function (array $data): array {
                    $tenant = Filament::getTenant();
                    abort_unless($tenant instanceof Company, 404);

                    return array_merge($data, [
                        'company_id' => $tenant->id,
                    ]);
                })
                ->successNotificationTitle('تم رفع المستند')
                ->form(fn (Form $form): Form => $this->documentForm($form, true)),
        ];
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('open')
                ->label('')
                ->icon('heroicon-o-arrow-down-tray')
                ->iconButton()
                ->size(ActionSize::Small)
                ->tooltip('تنزيل')
                ->url(fn (CompanyDocument $record): string => Storage::disk('public')->url($record->file_path))
                ->openUrlInNewTab(),
            Tables\Actions\EditAction::make()
                ->label('تعديل')
                ->icon('heroicon-o-pencil')
                ->iconButton()
                ->size(ActionSize::Small)
                ->tooltip('تعديل')
                ->modalHeading('تعديل مستند')
                ->modalWidth(MaxWidth::Large)
                ->modalSubmitActionLabel('حفظ')
                ->modalCancelActionLabel('إلغاء')
                ->successNotificationTitle('تم حفظ التعديلات')
                ->form(fn (Form $form): Form => $this->documentForm($form, false)),
            Tables\Actions\DeleteAction::make()
                ->label('حذف')
                ->icon('heroicon-o-trash')
                ->iconButton()
                ->size(ActionSize::Small)
                ->tooltip('حذف')
                ->modalHeading('حذف المستند')
                ->modalSubmitActionLabel('حذف')
                ->modalCancelActionLabel('إلغاء')
                ->successNotificationTitle('تم الحذف'),
        ];
    }

    protected function getTableActionsColumnLabel(): ?string
    {
        return '';
    }

    public function getTableModelLabel(): ?string
    {
        return 'مستند';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'مستندات';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا يوجد بيانات';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return null;
    }

    public function documentForm(Form $form, bool $fileRequired = true): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم المستند')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('file_path')
                    ->label('الملف')
                    ->disk('public')
                    ->directory(fn (): string => 'company-documents/'.Filament::getTenant()->getKey())
                    ->visibility('public')
                    ->downloadable()
                    ->openable()
                    ->maxSize(10240)
                    ->required($fileRequired)
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }

    public function getTitle(): string | Htmlable
    {
        return 'تحميل';
    }
}
