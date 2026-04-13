<?php

namespace App\Filament\Pages\Payroll;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollAllowance;
use App\Models\PayrollDeduction;
use App\Support\Payroll\EmployeePayrollAmounts;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
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

        return Employee::query()
            ->where('company_id', $tenant->id)
            ->orderBy('name_ar');
    }

    /**
     * @return Collection<int, PayrollAllowance>
     */
    private function payrollAllowancesForTenant(): Collection
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        static $byCompany = [];

        return $byCompany[$tenant->getKey()] ??= PayrollAllowance::query()
            ->where('company_id', $tenant->id)
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, PayrollDeduction>
     */
    private function payrollDeductionsForTenant(): Collection
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Company, 404);

        static $byCompany = [];

        return $byCompany[$tenant->getKey()] ??= PayrollDeduction::query()
            ->where('company_id', $tenant->id)
            ->orderBy('id')
            ->get();
    }

    private function amounts(Employee $employee): EmployeePayrollAmounts
    {
        static $cache = [];

        $id = $employee->getKey();

        return $cache[$id] ??= new EmployeePayrollAmounts(
            $employee,
            $this->payrollAllowancesForTenant(),
            $this->payrollDeductionsForTenant()
        );
    }

    /**
     * @return array<Tables\Columns\Column>
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('social_security_number')
                ->label('رقم الضمان')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('national_id')
                ->label('الرقم الوطني')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('name_ar')
                ->label('اسم الموظف')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('basic_salary')
                ->label('الراتب الاساسي')
                ->numeric(2),
            Tables\Columns\TextColumn::make('allowances_detail')
                ->label('العلاوات')
                ->getStateUsing(function (Employee $record): string {
                    $a = $this->amounts($record)->allowancesBreakdown();

                    return $a['labels'] !== '' ? $a['labels'] : '—';
                }),
            Tables\Columns\TextColumn::make('allowances_total')
                ->label('مجموع العلاوات')
                ->getStateUsing(fn (Employee $record): float => $this->amounts($record)->allowancesBreakdown()['total'])
                ->numeric(2),
            Tables\Columns\TextColumn::make('deductions_detail')
                ->label('الاقتطاعات')
                ->getStateUsing(function (Employee $record): string {
                    $d = $this->amounts($record)->deductionsBreakdown();

                    return $d['labels'] !== '' ? $d['labels'] : '—';
                }),
            Tables\Columns\TextColumn::make('deductions_total')
                ->label('مجموع الاقتطاعات')
                ->getStateUsing(fn (Employee $record): float => $this->amounts($record)->deductionsBreakdown()['total'])
                ->numeric(2),
            Tables\Columns\TextColumn::make('employee_ss')
                ->label('حصة الموظف بالضمان')
                ->getStateUsing(fn (Employee $record): float => $this->amounts($record)->employeeSocialSecurityShare())
                ->numeric(2),
            Tables\Columns\TextColumn::make('net_salary')
                ->label('صافي الراتب المحول على البنك')
                ->getStateUsing(fn (Employee $record): float => $this->amounts($record)->netSalaryTransferredToBank())
                ->numeric(2),
            Tables\Columns\TextColumn::make('company_ss')
                ->label('مساهمة الشركة في الضمان')
                ->getStateUsing(fn (Employee $record): float => $this->amounts($record)->companySocialSecurityShare())
                ->numeric(2),
            Tables\Columns\TextColumn::make('ss_remit_total')
                ->label('قيمة الضمان الواجب توريده')
                ->getStateUsing(fn (Employee $record): float => $this->amounts($record)->totalSocialSecurityToRemit())
                ->numeric(2),
        ];
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableHeaderActions(): array
    {
        return [];
    }

    /**
     * @return array<Tables\Actions\Action | Tables\Actions\ActionGroup>
     */
    protected function getTableActions(): array
    {
        return [];
    }

    protected function isTablePaginationEnabled(): bool
    {
        return true;
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'لا يوجد موظفون';
    }

    public function getTitle(): string|Htmlable
    {
        return 'كشف الرواتب';
    }
}
