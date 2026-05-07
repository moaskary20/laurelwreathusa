<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyInformationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_information_page_loads_for_authenticated_user(): void
    {
        $this->seed();

        $company = Company::query()->firstOrFail();
        $user = User::factory()->create([
            'company_id' => $company->getKey(),
            'is_main_user' => false,
            'is_super_user' => false,
        ]);

        $this->actingAs($user)
            ->get('/admin/'.$company->getKey().'/company-information')
            ->assertSuccessful();
    }
}
