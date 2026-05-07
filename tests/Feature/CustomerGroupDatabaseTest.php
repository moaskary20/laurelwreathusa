<?php

namespace Tests\Feature;

use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerGroupDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_is_linked_to_account_group_in_database(): void
    {
        $company = Company::query()->create([
            'legal_name' => 'شركة اختبار',
            'trade_name' => 'شركة اختبار',
            'legal_type' => 'individual',
            'trade_category' => 'commercial',
            'national_number' => '100',
            'registration_number' => '100',
            'sales_invoice_start' => 1,
            'objectives' => '—',
            'tax_number' => '100',
            'fiscal_year_end' => now()->endOfYear(),
            'inventory_system' => 'perpetual',
            'inventory_pricing' => 'average',
        ]);

        $group = AccountGroup::query()->create([
            'company_id' => $company->id,
            'code' => '110100',
            'name_ar' => 'عملاء محليون',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_postable' => true,
            'is_active' => true,
            'allow_manual_entries' => true,
            'sort_order' => 1,
            'level' => 1,
            'path' => '1',
        ]);

        $customer = Customer::query()->create([
            'company_id' => $company->id,
            'name_ar' => 'عميل اختبار',
            'name_en' => 'Test Customer',
            'payment_method' => 'cash',
            'account_group_id' => $group->id,
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'company_id' => $company->id,
            'account_group_id' => $group->id,
        ]);

        $this->assertTrue($customer->accountGroup->is($group));
        $this->assertSame('عملاء محليون', $customer->fresh()->accountGroup->name_ar);
    }
}
