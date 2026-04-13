<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CompanySeeder::class);

        $company = Company::query()->orderBy('id')->first();

        User::factory()->create([
            'name' => 'Test User',
            'name_ar' => 'Test User',
            'name_en' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'company_id' => $company?->getKey(),
        ]);
    }
}
