<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TenantAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_user_is_locked_to_their_company_only(): void
    {
        $this->seed();

        $company1 = Company::query()->orderBy('id')->firstOrFail();
        $company2 = Company::query()->create([
            'legal_name' => 'شركة 2',
            'trade_name' => 'شركة 2',
            'legal_type' => 'individual',
            'trade_category' => 'commercial',
            'national_number' => '2',
            'registration_number' => '2',
            'sales_invoice_start' => 1,
            'objectives' => '—',
            'email' => null,
            'phone' => null,
            'tax_number' => '2',
            'sales_tax_number' => null,
            'address' => null,
            'fax' => null,
            'po_box' => null,
            'fiscal_year_end' => now()->endOfYear(),
            'inventory_system' => 'perpetual',
            'inventory_pricing' => 'average',
        ]);

        $user = User::query()->create([
            'name' => 'User',
            'name_ar' => 'مستخدم',
            'name_en' => 'User',
            'username' => 'user1',
            'email' => 'user1@example.com',
            'password' => 'password',
            'company_id' => $company1->id,
            'is_main_user' => false,
            'is_super_user' => false,
            'permissions' => [],
        ]);

        /** @var Panel $panel */
        $panel = Mockery::mock(Panel::class);

        $tenants = $user->getTenants($panel);
        $this->assertCount(1, $tenants);
        $this->assertSame($company1->id, (int) $tenants->first()->getKey());

        $this->assertTrue($user->canAccessTenant($company1));
        $this->assertFalse($user->canAccessTenant($company2));

        $defaultTenant = $user->getDefaultTenant($panel);
        $this->assertNotNull($defaultTenant);
        $this->assertSame($company1->id, (int) $defaultTenant->getKey());
    }

    public function test_admin_user_can_see_and_access_all_companies(): void
    {
        $this->seed();

        $company1 = Company::query()->orderBy('id')->firstOrFail();
        $company2 = Company::query()->create([
            'legal_name' => 'شركة 2',
            'trade_name' => 'شركة 2',
            'legal_type' => 'individual',
            'trade_category' => 'commercial',
            'national_number' => '2',
            'registration_number' => '2',
            'sales_invoice_start' => 1,
            'objectives' => '—',
            'email' => null,
            'phone' => null,
            'tax_number' => '2',
            'sales_tax_number' => null,
            'address' => null,
            'fax' => null,
            'po_box' => null,
            'fiscal_year_end' => now()->endOfYear(),
            'inventory_system' => 'perpetual',
            'inventory_pricing' => 'average',
        ]);

        $admin = User::query()->where('is_super_user', true)->firstOrFail();

        /** @var Panel $panel */
        $panel = Mockery::mock(Panel::class);

        $tenants = $admin->getTenants($panel);
        $this->assertGreaterThanOrEqual(2, $tenants->count());

        $this->assertTrue($admin->canAccessTenant($company1));
        $this->assertTrue($admin->canAccessTenant($company2));
    }
}

