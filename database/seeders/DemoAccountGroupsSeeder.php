<?php

namespace Database\Seeders;

use App\Models\AccountGroup;
use App\Models\Company;
use App\Services\Accounting\ChartOfAccountsService;
use Illuminate\Database\Seeder;

/**
 * بيانات تجريبية لشجرة الحسابات الافتراضية.
 * التشغيل: php artisan db:seed --class=DemoAccountGroupsSeeder
 */
class DemoAccountGroupsSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(ChartOfAccountsService::class);

        foreach (Company::query()->cursor() as $company) {
            if (AccountGroup::query()->where('company_id', $company->id)->exists()) {
                $service->seedDefaultChart($company->id, pruneNonDefault: true);

                continue;
            }

            $service->seedDefaultChart($company->id);
        }
    }
}
