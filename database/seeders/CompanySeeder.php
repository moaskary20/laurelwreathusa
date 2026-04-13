<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Office;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        if (Company::query()->exists()) {
            return;
        }

        $company = Company::query()->create([
            'legal_name' => 'شركة افتراضية',
            'trade_name' => 'شركة افتراضية',
            'legal_type' => 'individual',
            'trade_category' => 'commercial',
            'national_number' => '1',
            'registration_number' => '1',
            'sales_invoice_start' => 1,
            'objectives' => '—',
            'email' => null,
            'phone' => null,
            'tax_number' => '1',
            'sales_tax_number' => null,
            'address' => null,
            'fax' => null,
            'po_box' => null,
            'fiscal_year_end' => now()->endOfYear(),
            'inventory_system' => 'perpetual',
            'inventory_pricing' => 'average',
        ]);

        Office::query()->whereNull('company_id')->update(['company_id' => $company->id]);
    }
}

