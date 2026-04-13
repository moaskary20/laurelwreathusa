<?php

namespace App\Support\Payroll;

use App\Models\Employee;
use App\Models\PayrollAllowance;
use App\Models\PayrollDeduction;
use Illuminate\Support\Collection;

/**
 * Monthly payroll figures for one employee (كشف الرواتب).
 *
 * الضمان: الراتب × النسب المحفوظة على الموظف.
 * العلاوات/الاقتطاعات: من تعريفات الشركة مع ربط بسيط (علاوة هاتف، نوع الاقتطاع على الموظف).
 */
final class EmployeePayrollAmounts
{
    /** @var array{total: float, labels: string}|null */
    private ?array $allowancesBreakdownCache = null;

    /** @var array{total: float, labels: string}|null */
    private ?array $deductionsBreakdownCache = null;

    public function __construct(
        private Employee $employee,
        private Collection $companyAllowances,
        private Collection $companyDeductions,
    ) {}

    public static function roundMoney(float $value): float
    {
        return round($value, 2);
    }

    public function employeeSocialSecurityShare(): float
    {
        $base = (float) $this->employee->basic_salary;
        $rate = (float) $this->employee->social_security_rate;

        return self::roundMoney($base * $rate);
    }

    public function companySocialSecurityShare(): float
    {
        $base = (float) $this->employee->basic_salary;
        $rate = (float) $this->employee->company_social_security_rate;

        return self::roundMoney($base * $rate);
    }

    public function totalSocialSecurityToRemit(): float
    {
        return self::roundMoney(
            $this->employeeSocialSecurityShare() + $this->companySocialSecurityShare()
        );
    }

    /**
     * @return array{total: float, labels: string}
     */
    public function allowancesBreakdown(): array
    {
        if ($this->allowancesBreakdownCache !== null) {
            return $this->allowancesBreakdownCache;
        }

        $parts = [];
        $total = 0.0;

        foreach ($this->companyAllowances as $allowance) {
            if (! $this->allowanceAppliesToEmployee($allowance)) {
                continue;
            }
            $monthly = self::monthlyPortion($allowance);
            $total += $monthly;
            $parts[] = $allowance->allowance_type;
        }

        return $this->allowancesBreakdownCache = [
            'total' => self::roundMoney($total),
            'labels' => implode('، ', array_filter($parts)),
        ];
    }

    /**
     * @return array{total: float, labels: string}
     */
    public function deductionsBreakdown(): array
    {
        if ($this->deductionsBreakdownCache !== null) {
            return $this->deductionsBreakdownCache;
        }

        $type = trim((string) ($this->employee->deduction_type ?? ''));
        if ($type === '') {
            return $this->deductionsBreakdownCache = ['total' => 0.0, 'labels' => ''];
        }

        $match = $this->companyDeductions->first(function (PayrollDeduction $d) use ($type): bool {
            return trim($d->deduction_type) === $type;
        });

        if ($match === null) {
            return $this->deductionsBreakdownCache = ['total' => 0.0, 'labels' => $type];
        }

        $monthly = self::monthlyPortionDeduction($match);

        return $this->deductionsBreakdownCache = [
            'total' => self::roundMoney($monthly),
            'labels' => $match->deduction_type,
        ];
    }

    public function netSalaryTransferredToBank(): float
    {
        $basic = (float) $this->employee->basic_salary;
        $allow = $this->allowancesBreakdown();
        $ded = $this->deductionsBreakdown();
        $empSs = $this->employeeSocialSecurityShare();

        return self::roundMoney($basic + $allow['total'] - $ded['total'] - $empSs);
    }

    private function allowanceAppliesToEmployee(PayrollAllowance $allowance): bool
    {
        if ($allowance->start_date !== null && $allowance->start_date->isFuture()) {
            return false;
        }

        $label = trim($allowance->allowance_type);
        if ($label === 'هاتف' && $this->employee->phone_allowance) {
            return true;
        }

        return false;
    }

    private static function monthlyPortion(PayrollAllowance $allowance): float
    {
        $amount = (float) $allowance->amount;

        return match ($allowance->frequency) {
            'yearly' => $amount / 12,
            'quarterly' => $amount / 3,
            'monthly' => $amount,
            default => $amount,
        };
    }

    private static function monthlyPortionDeduction(PayrollDeduction $deduction): float
    {
        $amount = (float) $deduction->amount;

        return match ($deduction->frequency) {
            'yearly' => $amount / 12,
            'quarterly' => $amount / 3,
            'monthly' => $amount,
            default => $amount,
        };
    }
}
