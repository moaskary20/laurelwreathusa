<?php

namespace Tests\Feature;

use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\ServiceProduct;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierAndInventoryGroupDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_is_linked_to_account_group_in_database(): void
    {
        $company = $this->createCompany();
        $group = $this->createPostingGroup($company, '210100', 'موردون محليون');

        $supplier = Supplier::query()->create([
            'company_id' => $company->id,
            'name_ar' => 'مورد اختبار',
            'name_en' => 'Test Supplier',
            'payment_method' => 'cash',
            'account_group_id' => $group->id,
        ]);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'company_id' => $company->id,
            'account_group_id' => $group->id,
        ]);

        $this->assertTrue($supplier->accountGroup->is($group));
        $this->assertSame('موردون محليون', $supplier->fresh()->accountGroup->name_ar);
    }

    public function test_inventory_item_is_linked_to_account_group_in_database(): void
    {
        $company = $this->createCompany();
        $group = $this->createPostingGroup($company, '130100', 'مخزون بضائع');

        $item = ServiceProduct::query()->create([
            'company_id' => $company->id,
            'kind' => 'product',
            'name_ar' => 'صنف اختبار',
            'name_en' => 'Test Item',
            'code' => 'ITEM-001',
            'sale_price' => 5,
            'stock_quantity' => 10,
            'unit_cost' => 3,
            'account_group_id' => $group->id,
        ]);

        $this->assertDatabaseHas('service_products', [
            'id' => $item->id,
            'company_id' => $company->id,
            'kind' => 'product',
            'account_group_id' => $group->id,
        ]);

        $this->assertTrue($item->accountGroup->is($group));
        $this->assertSame('مخزون بضائع', $item->fresh()->accountGroup->name_ar);
    }

    private function createCompany(): Company
    {
        return Company::query()->create([
            'legal_name' => 'شركة اختبار',
            'trade_name' => 'شركة اختبار',
            'legal_type' => 'individual',
            'trade_category' => 'commercial',
            'national_number' => '200',
            'registration_number' => '200',
            'sales_invoice_start' => 1,
            'objectives' => '—',
            'tax_number' => '200',
            'fiscal_year_end' => now()->endOfYear(),
            'inventory_system' => 'perpetual',
            'inventory_pricing' => 'average',
        ]);
    }

    private function createPostingGroup(Company $company, string $code, string $name): AccountGroup
    {
        return AccountGroup::query()->create([
            'company_id' => $company->id,
            'code' => $code,
            'name_ar' => $name,
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_postable' => true,
            'is_active' => true,
            'allow_manual_entries' => true,
            'sort_order' => 1,
            'level' => 1,
            'path' => '1',
        ]);
    }
}
