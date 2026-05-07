<?php

namespace App\Filament\Pages\Inventory;

use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\ServiceProduct;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\ActionSize;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property Table $table
 */
final class InventoryItemsListPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'inventory-items-list-page';

    protected static string $view = 'filament.pages.inventory.inventory-items-list';

    protected static ?string $navigationGroup = 'المخزون';

    protected static ?string $title = 'قائمة الاصناف';

    protected static ?string $navigationLabel = 'قائمة الاصناف';

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 3;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return ServiceProduct::query()
            ->where('company_id', $tenant->id)
            ->where('kind', 'product')
            ->with(['accountGroup']);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('code')
                ->label('رمز الصنف')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('name_ar')
                ->label('اسم الصنف')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('accountGroup.name_ar')
                ->label('المجموعات')
                ->placeholder('—'),
            Tables\Columns\TextColumn::make('stock_quantity')
                ->label('الكمية النهائية')
                ->numeric(0),
            Tables\Columns\TextColumn::make('inventory_line_value')
                ->label('القيمة النهائية')
                ->getStateUsing(fn (ServiceProduct $record): float => $record->inventoryLineValue())
                ->numeric(2),
        ];
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\Action::make('create')
                ->label('اضافة صنف +')
                ->icon('heroicon-o-plus')
                ->url(InventoryItemFormPage::getUrl()),
            Tables\Actions\Action::make('importCsv')
                ->label('تحميل اكسل')
                ->icon('heroicon-o-plus')
                ->color('warning')
                ->modalHeading('استيراد من ملف CSV')
                ->modalSubmitActionLabel('استيراد')
                ->modalCancelActionLabel('إلغاء')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('ملف CSV')
                        ->disk('public')
                        ->directory('imports')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->importInventoryItemsFromCsv($data['file'] ?? null);
                }),
            Tables\Actions\Action::make('exportCsv')
                ->label('اصدار الى اكسل')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (): StreamedResponse => $this->exportInventoryItemsCsv()),
        ];
    }

    protected function importInventoryItemsFromCsv(?string $path): void
    {
        if (! is_string($path) || $path === '') {
            Notification::make()->danger()->title('لم يُحدد ملف')->send();

            return;
        }

        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $fullPath = Storage::disk('public')->path($path);
        if (! is_readable($fullPath)) {
            Notification::make()->danger()->title('تعذر قراءة الملف')->send();

            return;
        }

        $handle = fopen($fullPath, 'r');
        if ($handle === false) {
            Notification::make()->danger()->title('تعذر فتح الملف')->send();

            return;
        }

        fgetcsv($handle);
        $count = 0;
        $invalidGroups = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) {
                continue;
            }
            $unitCost = isset($row[3]) ? (float) $row[3] : 0;
            $accountGroupId = isset($row[5]) && $row[5] !== '' ? (int) $row[5] : null;
            if ($accountGroupId !== null && ! $this->isValidInventoryAccountGroup($accountGroupId, $tenant)) {
                $accountGroupId = null;
                $invalidGroups++;
            }

            ServiceProduct::query()->updateOrCreate(
                [
                    'company_id' => $tenant->id,
                    'code' => (string) ($row[2] ?? ''),
                ],
                [
                    'kind' => 'product',
                    'name_ar' => $row[0] ?? '',
                    'name_en' => $row[1] ?? null,
                    'stock_quantity' => isset($row[4]) ? (float) $row[4] : 0,
                    'unit_cost' => $unitCost,
                    'sale_price' => $unitCost,
                    'account_group_id' => $accountGroupId,
                ]
            );
            $count++;
        }
        fclose($handle);

        Storage::disk('public')->delete($path);

        Notification::make()
            ->success()
            ->title('تم الاستيراد')
            ->body('عدد السجلات: '.$count.($invalidGroups > 0 ? '، وتم تجاهل '.$invalidGroups.' مجموعة غير صالحة' : ''))
            ->send();

        $this->resetTable();
    }

    protected function exportInventoryItemsCsv(): StreamedResponse
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $fileName = 'inventory-items-'.$tenant->id.'-'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($tenant): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'name_ar',
                'name_en',
                'code',
                'unit_cost',
                'stock_quantity',
                'account_group_id',
                'inventory_value',
            ]);

            ServiceProduct::query()
                ->where('company_id', $tenant->id)
                ->where('kind', 'product')
                ->orderBy('id')
                ->cursor()
                ->each(function (ServiceProduct $r) use ($out): void {
                    fputcsv($out, [
                        $r->name_ar,
                        $r->name_en,
                        $r->code,
                        $r->unit_cost,
                        $r->stock_quantity,
                        $r->account_group_id,
                        $r->inventoryLineValue(),
                    ]);
                });

            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('edit')
                ->label('')
                ->tooltip('تعديل')
                ->icon('heroicon-o-pencil')
                ->iconButton()
                ->size(ActionSize::Small)
                ->url(fn (ServiceProduct $record): string => InventoryItemFormPage::getUrl().'?id='.$record->getKey()),
            Tables\Actions\DeleteAction::make()
                ->label('')
                ->tooltip('حذف')
                ->icon('heroicon-o-trash')
                ->iconButton()
                ->size(ActionSize::Small)
                ->modalHeading('حذف الصنف')
                ->modalSubmitActionLabel('حذف')
                ->modalCancelActionLabel('إلغاء')
                ->successNotificationTitle('تم الحذف'),
        ];
    }

    protected function getTableActionsColumnLabel(): ?string
    {
        return 'خيارات';
    }

    public function getTableModelLabel(): ?string
    {
        return 'صنف';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'أصناف';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا توجد أصناف';
    }

    public function getTitle(): string|Htmlable
    {
        return 'قائمة الاصناف';
    }

    protected function isValidInventoryAccountGroup(int $accountGroupId, Company $tenant): bool
    {
        return AccountGroup::query()
            ->whereKey($accountGroupId)
            ->where('company_id', $tenant->id)
            ->where('is_active', true)
            ->where('is_postable', true)
            ->exists();
    }
}
