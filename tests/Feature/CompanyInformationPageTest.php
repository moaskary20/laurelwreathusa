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

        $user = User::factory()->create();
        $company = Company::query()->firstOrFail();

        $this->actingAs($user)
            ->get('/admin/'.$company->getKey().'/company-information')
            ->assertSuccessful();
    }
}
