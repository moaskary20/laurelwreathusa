<?php

namespace App\Support\Payroll;

use App\Models\Employee;
use App\Models\PayrollAllowance;
use App\Models\PayrollDeduction;
use Carbon\CarbonInterface;
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
        private ?CarbonInterface $referenceDate = null,
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

        $items = $this->allowanceItems();
        $total = array_sum(array_column($items, 'amount'));
        $parts = array_column($items, 'label');

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

        $items = $this->deductionItems();
        $total = array_sum(array_column($items, 'amount'));
        $parts = array_column($items, 'label');

        return $this->deductionsBreakdownCache = [
            'total' => self::roundMoney($total),
            'labels' => implode('، ', array_filter($parts)),
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

    /**
     * @return list<array{type: string, source_id: int|null, label: string, amount: float}>
     */
    public function allowanceItems(): array
    {
        $items = [];

        foreach ($this->companyAllowances as $allowance) {
            if (! $this->allowanceAppliesToEmployee($allowance)) {
                continue;
            }

            $items[] = [
                'type' => 'allowance',
                'source_id' => $allowance->getKey(),
                'label' => (string) $allowance->allowance_type,
                'amount' => self::roundMoney(self::monthlyPortion($allowance)),
            ];
        }

        return $items;
    }

    /**
     * @return list<array{type: string, source_id: int|null, label: string, amount: float}>
     */
    public function deductionItems(): array
    {
        $type = trim((string) ($this->employee->deduction_type ?? ''));
        if ($type === '') {
            return [];
        }

        $match = $this->companyDeductions->first(function (PayrollDeduction $d) use ($type): bool {
            return trim($d->deduction_type) === $type;
        });

        if (! $match instanceof PayrollDeduction) {
            return [[
                'type' => 'deduction',
                'source_id' => null,
                'label' => $type,
                'amount' => 0.0,
            ]];
        }

        return [[
            'type' => 'deduction',
            'source_id' => $match->getKey(),
            'label' => (string) $match->deduction_type,
            'amount' => self::roundMoney(self::monthlyPortionDeduction($match)),
        ]];
    }

    private function allowanceAppliesToEmployee(PayrollAllowance $allowance): bool
    {
        $referenceDate = $this->referenceDate ?? now();
        if ($allowance->start_date !== null && $allowance->start_date->greaterThan($referenceDate)) {
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
