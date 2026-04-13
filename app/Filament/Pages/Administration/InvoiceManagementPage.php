<?php

namespace App\Filament\Pages\Administration;

use App\Models\Company;
use App\Models\InvoiceText;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property \Filament\Tables\Table $table
 */
final class InvoiceManagementPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'invoice-management';

    protected static string $view = 'filament.pages.administration.invoice-management';

    protected static ?string $navigationGroup = 'إدارة';

    protected static ?string $title = 'إدارة الفواتير';

    protected static ?string $navigationLabel = 'إدارة الفواتير';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 5;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return InvoiceText::query()->where('company_id', $tenant->id);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('title')
                ->label('عنوان النص')
                ->searchable()
                ->sortable()
                ->placeholder('—')
                ->wrap(),
        ];
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\CreateAction::make()
                ->label('اضافة')
                ->icon('heroicon-o-plus')
                ->model(InvoiceText::class)
                ->modalHeading('اضافة نص')
                ->modalWidth(MaxWidth::FourExtraLarge)
                ->createAnother(false)
                ->mutateFormDataUsing(function (array $data): array {
                    $tenant = Filament::getTenant();
                    abort_unless($tenant instanceof Company, 404);

                    return array_merge($data, [
                        'company_id' => $tenant->id,
                    ]);
                })
                ->successNotificationTitle('تمت إضافة النص')
                ->form(fn (Form $form): Form => $this->invoiceTextForm($form)),
        ];
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableActions(): array
    {
        return [
            Tables\Actions\EditAction::make()
                ->label('تعديل')
                ->modalHeading('تعديل نص')
                ->modalWidth(MaxWidth::FourExtraLarge)
                ->successNotificationTitle('تم حفظ التعديلات')
                ->form(fn (Form $form): Form => $this->invoiceTextForm($form)),
            Tables\Actions\DeleteAction::make()
                ->label('حذف')
                ->modalHeading('حذف النص')
                ->successNotificationTitle('تم الحذف'),
        ];
    }

    protected function getTableActionsColumnLabel(): ?string
    {
        return 'الإجراءات';
    }

    public function getTableModelLabel(): ?string
    {
        return 'نص';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'نصوص';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا توجد نصوص';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'اضغط «اضافة» لإنشاء نص جديد للفواتير.';
    }

    public function invoiceTextForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('عنوان النص :')
                    ->maxLength(500)
                    ->columnSpanFull(),
                Forms\Components\View::make('filament.forms.invoice-text-divider')
                    ->columnSpanFull(),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Textarea::make('text_ar')
                            ->label('النص باللغة العربية :')
                            ->rows(10)
                            ->extraInputAttributes(['class' => 'ci-invoice-textarea']),
                        Forms\Components\Textarea::make('text_en')
                            ->label('النص باللغة الانجليزية :')
                            ->rows(10)
                            ->extraInputAttributes(['class' => 'ci-invoice-textarea']),
                    ]),
            ])
            ->columns(1);
    }

    public function getTitle(): string | Htmlable
    {
        return 'إدارة الفواتير';
    }
}
