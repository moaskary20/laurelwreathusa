<?php

namespace Tests\Feature;

use App\Models\AssetCategory;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Employee;
use App\Models\FixedAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetsAndPayrollDatabaseLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_fixed_asset_is_linked_to_asset_category_in_database(): void
    {
        $company = $this->createCompany();
        $category = AssetCategory::query()->create([
            'company_id' => $company->id,
            'name_ar' => 'مركبات',
            'annual_depreciation_rate' => 20,
        ]);

        $asset = FixedAsset::query()->create([
            'company_id' => $company->id,
            'name' => 'سيارة اختبار',
            'asset_category_id' => $category->id,
            'historical_cost' => 10000,
            'annual_depreciation_rate' => 20,
        ]);

        $this->assertDatabaseHas('fixed_assets', [
            'id' => $asset->id,
            'company_id' => $company->id,
            'asset_category_id' => $category->id,
        ]);

        $this->assertTrue($asset->assetCategory->is($category));
        $this->assertSame('مركبات', $asset->fresh()->assetCategory->name_ar);
    }

    public function test_employee_is_linked_to_cost_center_in_database(): void
    {
        $company = $this->createCompany();
        $costCenter = CostCenter::query()->create([
            'company_id' => $company->id,
            'name_ar' => 'الإدارة',
            'name_en' => 'Administration',
            'code' => 'ADM',
        ]);

        $employee = Employee::query()->create([
            'company_id' => $company->id,
            'name_ar' => 'موظف اختبار',
            'name_en' => 'Test Employee',
            'national_id' => '1234567890',
            'social_security_number' => 'SS-1',
            'hiring_date' => now()->toDateString(),
            'job_number' => 'EMP-001',
            'basic_salary' => 500,
            'social_security_rate' => 7.5,
            'company_social_security_rate' => 14.25,
            'commission_rate' => 0,
            'marital_status' => 'single',
            'phone_allowance' => false,
            'cost_center_id' => $costCenter->id,
        ]);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'company_id' => $company->id,
            'cost_center_id' => $costCenter->id,
        ]);

        $this->assertTrue($employee->costCenter->is($costCenter));
        $this->assertSame('الإدارة', $employee->fresh()->costCenter->name_ar);
    }

    private function createCompany(): Company
    {
        return Company::query()->create([
            'legal_name' => 'شركة اختبار',
            'trade_name' => 'شركة اختبار',
            'legal_type' => 'individual',
            'trade_category' => 'commercial',
            'national_number' => '300',
            'registration_number' => '300',
            'sales_invoice_start' => 1,
            'objectives' => '—',
            'tax_number' => '300',
            'fiscal_year_end' => now()->endOfYear(),
            'inventory_system' => 'perpetual',
            'inventory_pricing' => 'average',
        ]);
    }
}
