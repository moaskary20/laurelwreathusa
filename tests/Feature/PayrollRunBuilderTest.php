<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollAllowance;
use App\Models\PayrollDeduction;
use App\Models\PayrollRun;
use App\Support\Payroll\PayrollRunBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollRunBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_monthly_payroll_run_lines_and_totals(): void
    {
        $this->seed();

        $company = Company::query()->firstOrFail();

        Employee::query()->create([
            'company_id' => $company->id,
            'name_ar' => 'موظف 1',
            'name_en' => 'Emp 1',
            'national_id' => '1001',
            'social_security_number' => 'SS-1',
            'hiring_date' => now()->subMonths(2)->toDateString(),
            'job_number' => 'E001',
            'basic_salary' => 1000,
            'social_security_rate' => 0.075,
            'company_social_security_rate' => 0.14,
            'commission_rate' => 0,
            'marital_status' => 'single',
            'phone_allowance' => true,
            'deduction_type' => 'قرض',
            'cost_center_id' => null,
        ]);

        PayrollAllowance::query()->create([
            'company_id' => $company->id,
            'allowance_type' => 'هاتف',
            'amount' => 120,
            'frequency' => 'monthly',
            'start_date' => now()->subMonth()->toDateString(),
        ]);

        PayrollDeduction::query()->create([
            'company_id' => $company->id,
            'deduction_type' => 'قرض',
            'amount' => 50,
            'frequency' => 'monthly',
            'start_date' => now()->subMonth()->toDateString(),
        ]);

        $run = PayrollRun::query()->create([
            'company_id' => $company->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'status' => PayrollRun::STATUS_DRAFT,
        ]);

        app(PayrollRunBuilder::class)->rebuild($run);

        $run->refresh();
        $line = $run->lines()->with('items')->firstOrFail();

        $this->assertSame(1, $run->employees_count);
        $this->assertEquals(1000.0, (float) $run->gross_total);
        $this->assertEquals(120.0, (float) $run->allowances_total);
        $this->assertEquals(50.0, (float) $run->deductions_total);
        $this->assertEquals(75.0, (float) $run->employee_ss_total);
        $this->assertEquals(140.0, (float) $run->company_ss_total);
        $this->assertEquals(995.0, (float) $run->net_total);

        $this->assertCount(2, $line->items);
    }

    public function test_company_and_month_unique_constraint_exists(): void
    {
        $this->seed();

        $company = Company::query()->firstOrFail();

        $first = PayrollRun::query()->create([
            'company_id' => $company->id,
            'period_month' => '2026-05-01',
            'status' => PayrollRun::STATUS_DRAFT,
        ]);

        $this->assertNotNull($first->id);

        $this->expectException(\Illuminate\Database\QueryException::class);

        PayrollRun::query()->create([
            'company_id' => $company->id,
            'period_month' => '2026-05-01',
            'status' => PayrollRun::STATUS_DRAFT,
        ]);
    }
}
