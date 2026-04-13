<?php

namespace App\Filament\Pages\Payroll;

use App\Models\Company;
use App\Models\Employee;
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
final class EmployeeDefinitionPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'employee-definition-page';

    protected static string $view = 'filament.pages.payroll.employee-definition-list';

    protected static ?string $navigationGroup = 'كشف الرواتب';

    protected static ?string $title = 'تعريف الموظف';

    protected static ?string $navigationLabel = 'تعريف الموظف';

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?int $navigationSort = 1;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Company, 404);

        return Employee::query()
            ->where('company_id', $tenant->id)
            ->with(['costCenter']);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name_ar')
                ->label('اسم الموظف')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('national_id')
                ->label('الرقم الوطني')
                ->searchable(),
            Tables\Columns\TextColumn::make('social_security_number')
                ->label('رقم الضمان')
                ->searchable(),
            Tables\Columns\TextColumn::make('hiring_date')
                ->label('تاريخ التعيين')
                ->date('Y-m-d')
                ->sortable(),
            Tables\Columns\TextColumn::make('job_number')
                ->label('الرقم الوظيفي')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('basic_salary')
                ->label('الراتب الاساسي')
                ->numeric(2),
            Tables\Columns\TextColumn::make('social_security_rate')
                ->label('نسبة الضمان الاجتماعي')
                ->numeric(4),
            Tables\Columns\TextColumn::make('company_social_security_rate')
                ->label('نسبة الشركة بالضمان الاجتماعي')
                ->numeric(4),
            Tables\Columns\TextColumn::make('marital_status')
                ->label('اعزب/متزوج')
                ->formatStateUsing(fn (?string $state): string => match ($state) {
                    'married' => 'متزوج',
                    'single' => 'اعزب',
                    default => (string) $state,
                }),
            Tables\Columns\TextColumn::make('deduction_type')
                ->label('نوع الاقتطاع')
                ->placeholder('—'),
        ];
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\Action::make('create')
                ->label('اضافة الموظف')
                ->icon('heroicon-o-plus')
                ->url(EmployeeDefinitionFormPage::getUrl()),
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
                    $this->importEmployeesFromCsv($data['file'] ?? null);
                }),
            Tables\Actions\Action::make('exportCsv')
                ->label('اصدار الى اكسل')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (): StreamedResponse => $this->exportEmployeesCsv()),
        ];
    }

    protected function importEmployeesFromCsv(?string $path): void
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
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 6) {
                continue;
            }
            $jobNumber = (string) ($row[4] ?? '');
            if ($jobNumber === '') {
                continue;
            }

            Employee::query()->updateOrCreate(
                [
                    'company_id' => $tenant->id,
                    'job_number' => $jobNumber,
                ],
                [
                    'name_ar' => $row[0] ?? '',
                    'name_en' => $row[1] ?? null,
                    'national_id' => (string) ($row[2] ?? ''),
                    'social_security_number' => (string) ($row[3] ?? ''),
                    'hiring_date' => $row[5] ?? now()->toDateString(),
                    'termination_date' => ($row[6] ?? '') !== '' ? $row[6] : null,
                    'basic_salary' => isset($row[7]) ? (float) $row[7] : 0,
                    'social_security_rate' => isset($row[8]) ? (float) $row[8] : 0,
                    'company_social_security_rate' => isset($row[9]) ? (float) $row[9] : 0,
                    'commission_rate' => isset($row[10]) ? (float) $row[10] : 0,
                    'marital_status' => ($row[11] ?? '') === 'married' ? 'married' : 'single',
                    'phone_allowance' => isset($row[12]) && in_array(strtolower((string) $row[12]), ['1', 'true', 'yes'], true),
                    'deduction_type' => ($row[13] ?? '') !== '' ? $row[13] : null,
                    'cost_center_id' => isset($row[14]) && $row[14] !== '' ? (int) $row[14] : null,
                ]
            );
            $count++;
        }
        fclose($handle);

        Storage::disk('public')->delete($path);

        Notification::make()
            ->success()
            ->title('تم الاستيراد')
            ->body('عدد السجلات: '.$count)
            ->send();

        $this->resetTable();
    }

    protected function exportEmployeesCsv(): StreamedResponse
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $fileName = 'employees-'.$tenant->id.'-'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($tenant): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'name_ar',
                'name_en',
                'national_id',
                'social_security_number',
                'job_number',
                'hiring_date',
                'termination_date',
                'basic_salary',
                'social_security_rate',
                'company_social_security_rate',
                'commission_rate',
                'marital_status',
                'phone_allowance',
                'deduction_type',
                'cost_center_id',
            ]);

            Employee::query()
                ->where('company_id', $tenant->id)
                ->orderBy('id')
                ->cursor()
                ->each(function (Employee $r) use ($out): void {
                    fputcsv($out, [
                        $r->name_ar,
                        $r->name_en,
                        $r->national_id,
                        $r->social_security_number,
                        $r->job_number,
                        $r->hiring_date?->format('Y-m-d'),
                        $r->termination_date?->format('Y-m-d'),
                        $r->basic_salary,
                        $r->social_security_rate,
                        $r->company_social_security_rate,
                        $r->commission_rate,
                        $r->marital_status,
                        $r->phone_allowance ? '1' : '0',
                        $r->deduction_type,
                        $r->cost_center_id,
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
                ->url(fn (Employee $record): string => EmployeeDefinitionFormPage::getUrl().'?id='.$record->getKey()),
            Tables\Actions\DeleteAction::make()
                ->label('')
                ->tooltip('حذف')
                ->icon('heroicon-o-trash')
                ->iconButton()
                ->size(ActionSize::Small)
                ->modalHeading('حذف الموظف')
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
        return 'موظف';
    }

    public function getTablePluralModelLabel(): ?string
    {
        return 'موظفون';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا يوجد موظفون';
    }

    public function getTitle(): string|Htmlable
    {
        return 'تعريف الموظف';
    }
}
