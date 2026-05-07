<?php

namespace App\Support\Payroll;

use App\Models\Employee;
use App\Models\PayrollAllowance;
use App\Models\PayrollDeduction;
use App\Models\PayrollRun;
use Illuminate\Support\Facades\DB;

final class PayrollRunBuilder
{
    public function rebuild(PayrollRun $run): PayrollRun
    {
        return DB::transaction(function () use ($run): PayrollRun {
            $run->loadMissing('company');

            $employees = Employee::query()
                ->where('company_id', $run->company_id)
                ->whereDate('hiring_date', '<=', $run->period_month->copy()->endOfMonth())
                ->where(function ($query) use ($run): void {
                    $query->whereNull('termination_date')
                        ->orWhereDate('termination_date', '>=', $run->period_month->copy()->startOfMonth());
                })
                ->orderBy('name_ar')
                ->get();

            $allowances = PayrollAllowance::query()
                ->where('company_id', $run->company_id)
                ->orderBy('id')
                ->get();

            $deductions = PayrollDeduction::query()
                ->where('company_id', $run->company_id)
                ->orderBy('id')
                ->get();

            $run->lines()->delete();

            $grossTotal = 0.0;
            $allowancesTotal = 0.0;
            $deductionsTotal = 0.0;
            $employeeSsTotal = 0.0;
            $companySsTotal = 0.0;
            $netTotal = 0.0;

            foreach ($employees as $employee) {
                $amounts = new EmployeePayrollAmounts(
                    $employee,
                    $allowances,
                    $deductions,
                    $run->period_month->copy()->endOfMonth()
                );

                $allowanceBreakdown = $amounts->allowancesBreakdown();
                $deductionBreakdown = $amounts->deductionsBreakdown();
                $employeeSs = $amounts->employeeSocialSecurityShare();
                $companySs = $amounts->companySocialSecurityShare();
                $net = $amounts->netSalaryTransferredToBank();
                $basic = (float) $employee->basic_salary;

                $line = $run->lines()->create([
                    'employee_id' => $employee->id,
                    'cost_center_id' => $employee->cost_center_id,
                    'basic_salary' => $basic,
                    'allowances_total' => $allowanceBreakdown['total'],
                    'deductions_total' => $deductionBreakdown['total'],
                    'employee_social_security' => $employeeSs,
                    'company_social_security' => $companySs,
                    'social_security_total' => $amounts->totalSocialSecurityToRemit(),
                    'net_salary' => $net,
                    'meta' => [
                        'allowances_labels' => $allowanceBreakdown['labels'],
                        'deductions_labels' => $deductionBreakdown['labels'],
                    ],
                ]);

                foreach ($amounts->allowanceItems() as $item) {
                    $line->items()->create($item);
                }

                foreach ($amounts->deductionItems() as $item) {
                    $line->items()->create($item);
                }

                $grossTotal += $basic;
                $allowancesTotal += $allowanceBreakdown['total'];
                $deductionsTotal += $deductionBreakdown['total'];
                $employeeSsTotal += $employeeSs;
                $companySsTotal += $companySs;
                $netTotal += $net;
            }

            $run->update([
                'employees_count' => $employees->count(),
                'gross_total' => EmployeePayrollAmounts::roundMoney($grossTotal),
                'allowances_total' => EmployeePayrollAmounts::roundMoney($allowancesTotal),
                'deductions_total' => EmployeePayrollAmounts::roundMoney($deductionsTotal),
                'employee_ss_total' => EmployeePayrollAmounts::roundMoney($employeeSsTotal),
                'company_ss_total' => EmployeePayrollAmounts::roundMoney($companySsTotal),
                'net_total' => EmployeePayrollAmounts::roundMoney($netTotal),
            ]);

            return $run->fresh(['lines.employee', 'lines.items']);
        });
    }
}
