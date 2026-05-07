<?php

namespace App\Filament\Pages\Payroll;

use App\Models\Company;
use App\Models\CostCenter;
use App\Models\PayrollRun;
use App\Models\PayrollRunLine;
use App\Support\Payroll\PayrollRunBuilder;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * كشف الرواتب — عرض شهري للموظفين مع حساب الضمان والصافي.
 *
 * @property Table $table
 */
final class PayrollStatementPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $slug = 'payroll-statement-page';

    protected static string $view = 'filament.pages.payroll.payroll-statement';

    protected static ?string $navigationGroup = 'كشف الرواتب';

    protected static ?string $title = 'كشف الرواتب';

    protected static ?string $navigationLabel = 'كشف الرواتب';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 4;

    protected ?string $heading = '';

    protected ?string $maxContentWidth = 'full';

    public ?int $selectedRunId = null;

    public function mount(): void
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        $this->selectedRunId = PayrollRun::query()
            ->where('company_id', $tenant->id)
            ->latest('period_month')
            ->value('id');
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('createRun')
                ->label('إنشاء دورة شهرية')
                ->icon('heroicon-o-plus')
                ->form([
                    Forms\Components\DatePicker::make('period_month')
                        ->label('شهر الرواتب')
                        ->required()
                        ->displayFormat('Y-m')
                        ->native(false),
                ])
                ->action(function (array $data): void {
                    $tenant = Filament::getTenant();
                    abort_unless($tenant instanceof Company, 404);

                    $periodMonth = \Illuminate\Support\Carbon::parse($data['period_month'])->startOfMonth();

                    $exists = PayrollRun::query()
                        ->where('company_id', $tenant->id)
                        ->whereDate('period_month', $periodMonth->toDateString())
                        ->exists();

                    if ($exists) {
                        Notification::make()
                            ->danger()
                            ->title('الدورة موجودة مسبقًا لنفس الشهر')
                            ->send();

                        return;
                    }

                    $run = PayrollRun::query()->create([
                        'company_id' => $tenant->id,
                        'period_month' => $periodMonth,
                        'status' => PayrollRun::STATUS_DRAFT,
                    ]);

                    app(PayrollRunBuilder::class)->rebuild($run);
                    $this->selectedRunId = $run->id;
                    $this->resetTable();

                    Notification::make()->success()->title('تم إنشاء الدورة واحتساب الرواتب')->send();
                }),
            Action::make('rebuildRun')
                ->label('إعادة الاحتساب')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function (): void {
                    $run = $this->currentRun();
                    if (! $run instanceof PayrollRun) {
                        Notification::make()->danger()->title('اختر دورة رواتب أولًا')->send();

                        return;
                    }

                    if (! $run->isDraft()) {
                        Notification::make()->danger()->title('لا يمكن إعادة احتساب دورة معتمدة')->send();

                        return;
                    }

                    app(PayrollRunBuilder::class)->rebuild($run);
                    $this->resetTable();
                    Notification::make()->success()->title('تم إعادة احتساب الدورة')->send();
                }),
            Action::make('finalizeRun')
                ->label('اعتماد الدورة')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->action(function (): void {
                    $run = $this->currentRun();
                    if (! $run instanceof PayrollRun) {
                        Notification::make()->danger()->title('اختر دورة رواتب أولًا')->send();

                        return;
                    }

                    if (! $run->isDraft()) {
                        Notification::make()->danger()->title('الدورة معتمدة مسبقًا')->send();

                        return;
                    }

                    $run->update([
                        'status' => PayrollRun::STATUS_FINALIZED,
                        'finalized_at' => now(),
                    ]);

                    Notification::make()->success()->title('تم اعتماد دورة الرواتب')->send();
                }),
            Action::make('print')
                ->label('طباعه')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn () => $this->js('window.print()')),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        return PayrollRunLine::query()
            ->where('payroll_run_id', $this->selectedRunId ?: 0)
            ->whereHas('payrollRun', fn (Builder $query): Builder => $query->where('company_id', $tenant->id))
            ->with(['employee.costCenter', 'items', 'payrollRun']);
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('employee.social_security_number')
                ->label('رقم الضمان')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('employee.national_id')
                ->label('الرقم الوطني')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('employee.name_ar')
                ->label('اسم الموظف')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('employee.job_number')
                ->label('الرقم الوظيفي')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('basic_salary')
                ->label('الراتب الاساسي')
                ->numeric(2)
                ->sortable(),
            Tables\Columns\TextColumn::make('allowances_total')
                ->label('مجموع العلاوات')
                ->numeric(2)
                ->sortable(),
            Tables\Columns\TextColumn::make('deductions_total')
                ->label('مجموع الاقتطاعات')
                ->numeric(2)
                ->sortable(),
            Tables\Columns\TextColumn::make('employee_social_security')
                ->label('حصة الموظف بالضمان')
                ->numeric(2)
                ->sortable(),
            Tables\Columns\TextColumn::make('net_salary')
                ->label('صافي الراتب المحول على البنك')
                ->numeric(2)
                ->sortable(),
            Tables\Columns\TextColumn::make('company_social_security')
                ->label('مساهمة الشركة في الضمان')
                ->numeric(2)
                ->sortable(),
            Tables\Columns\TextColumn::make('social_security_total')
                ->label('قيمة الضمان الواجب توريده')
                ->numeric(2)
                ->sortable(),
        ];
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\Action::make('exportCsv')
                ->label('إصدار إلى إكسل')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (): StreamedResponse => $this->exportPayrollCsv()),
        ];
    }

    protected function getTableFilters(): array
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        return [
            SelectFilter::make('payroll_run_id')
                ->label('الشهر')
                ->options(
                    PayrollRun::query()
                        ->where('company_id', $tenant->id)
                        ->orderByDesc('period_month')
                        ->get()
                        ->mapWithKeys(fn (PayrollRun $run): array => [
                            $run->id => $run->period_month?->format('Y-m').' - '.($run->status === PayrollRun::STATUS_FINALIZED ? 'معتمد' : 'مسودة'),
                        ])
                        ->all()
                )
                ->default($this->selectedRunId)
                ->query(function (Builder $query, array $state): Builder {
                    $runId = (int) ($state['value'] ?? 0);
                    if ($runId > 0) {
                        $this->selectedRunId = $runId;
                    }

                    return $query->where('payroll_run_id', $runId > 0 ? $runId : -1);
                }),
            SelectFilter::make('cost_center_id')
                ->label('مركز الكلفة')
                ->options(
                    CostCenter::query()
                        ->where('company_id', $tenant->id)
                        ->orderBy('name_ar')
                        ->pluck('name_ar', 'id')
                        ->all()
                )
                ->query(function (Builder $query, array $state): Builder {
                    $value = $state['value'] ?? null;

                    return filled($value) ? $query->where('cost_center_id', (int) $value) : $query;
                }),
            SelectFilter::make('employment_status')
                ->label('الحالة الوظيفية')
                ->options([
                    'active' => 'على رأس العمل',
                    'terminated' => 'منتهي الخدمة',
                ])
                ->query(function (Builder $query, array $state): Builder {
                    $value = (string) ($state['value'] ?? '');

                    return match ($value) {
                        'active' => $query->whereHas('employee', fn (Builder $q): Builder => $q->whereNull('termination_date')),
                        'terminated' => $query->whereHas('employee', fn (Builder $q): Builder => $q->whereNotNull('termination_date')),
                        default => $query,
                    };
                }),
        ];
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('details')
                ->label('تفاصيل')
                ->icon('heroicon-o-eye')
                ->modalHeading('تفاصيل الموظف')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('إغلاق')
                ->modalContent(fn (PayrollRunLine $record) => view('filament.pages.payroll.partials.payroll-line-details', [
                    'line' => $record->loadMissing(['employee.costCenter', 'items', 'payrollRun']),
                ])),
        ];
    }

    protected function isTablePaginationEnabled(): bool
    {
        return true;
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا توجد بيانات لهذا الشهر';
    }

    public function getTitle(): string|Htmlable
    {
        return 'كشف الرواتب';
    }

    public function selectedRunLabel(): string
    {
        $run = $this->currentRun();
        if (! $run instanceof PayrollRun) {
            return 'لا توجد دورة محددة';
        }

        $status = $run->status === PayrollRun::STATUS_FINALIZED ? 'معتمد' : 'مسودة';

        return 'شهر '.$run->period_month?->format('Y-m').' - '.$status;
    }

    public function runTotals(): array
    {
        $run = $this->currentRun();
        if (! $run instanceof PayrollRun) {
            return [
                'employees_count' => 0,
                'gross_total' => 0,
                'allowances_total' => 0,
                'deductions_total' => 0,
                'employee_ss_total' => 0,
                'company_ss_total' => 0,
                'net_total' => 0,
            ];
        }

        return [
            'employees_count' => (int) $run->employees_count,
            'gross_total' => (float) $run->gross_total,
            'allowances_total' => (float) $run->allowances_total,
            'deductions_total' => (float) $run->deductions_total,
            'employee_ss_total' => (float) $run->employee_ss_total,
            'company_ss_total' => (float) $run->company_ss_total,
            'net_total' => (float) $run->net_total,
        ];
    }

    private function currentRun(): ?PayrollRun
    {
        if (! $this->selectedRunId) {
            return null;
        }

        $tenant = Filament::getTenant();
        if (! $tenant instanceof Company) {
            return null;
        }

        return PayrollRun::query()
            ->where('company_id', $tenant->id)
            ->find($this->selectedRunId);
    }

    public function exportPayrollCsv(): StreamedResponse
    {
        $run = $this->currentRun();
        abort_unless($run instanceof PayrollRun, 404);

        $fileName = 'payroll-'.$run->period_month?->format('Y-m').'-'.now()->format('Y-m-d_His').'.csv';
        $query = $this->getFilteredSortedTableQuery()->with(['employee', 'items']);

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'employee_name',
                'job_number',
                'national_id',
                'social_security_number',
                'cost_center',
                'basic_salary',
                'allowances_total',
                'deductions_total',
                'employee_social_security',
                'company_social_security',
                'social_security_total',
                'net_salary',
            ]);

            $query->cursor()->each(function (PayrollRunLine $line) use ($out): void {
                fputcsv($out, [
                    $line->employee?->name_ar,
                    $line->employee?->job_number,
                    $line->employee?->national_id,
                    $line->employee?->social_security_number,
                    $line->employee?->costCenter?->name_ar,
                    $line->basic_salary,
                    $line->allowances_total,
                    $line->deductions_total,
                    $line->employee_social_security,
                    $line->company_social_security,
                    $line->social_security_total,
                    $line->net_salary,
                ]);
            });

            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
