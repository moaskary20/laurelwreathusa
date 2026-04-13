<?php

namespace Database\Seeders;

use App\Models\AccountGroup;
use App\Models\Company;
use Illuminate\Database\Seeder;

/**
 * بيانات تجريبية لمجموعات الحسابات (مثال لقائمة «المجموعات» في تعريف الضريبة).
 * التشغيل: php artisan db:seed --class=DemoAccountGroupsSeeder
 */
class DemoAccountGroupsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Company::query()->cursor() as $company) {
            if (AccountGroup::query()->where('company_id', $company->id)->exists()) {
                continue;
            }

            $current = AccountGroup::query()->create([
                'company_id' => $company->id,
                'parent_id' => null,
                'name_ar' => 'الموجودات المتداولة',
                'sort_order' => 1,
            ]);

            $cash = AccountGroup::query()->create([
                'company_id' => $company->id,
                'parent_id' => $current->id,
                'name_ar' => 'النقد وما في حكمه',
                'sort_order' => 1,
            ]);

            AccountGroup::query()->create([
                'company_id' => $company->id,
                'parent_id' => $cash->id,
                'name_ar' => 'نقد في الصندوق',
                'sort_order' => 1,
            ]);

            AccountGroup::query()->create([
                'company_id' => $company->id,
                'parent_id' => $cash->id,
                'name_ar' => 'نقد لدى البنوك',
                'sort_order' => 2,
            ]);

            AccountGroup::query()->create([
                'company_id' => $company->id,
                'parent_id' => $current->id,
                'name_ar' => 'الذمم المدينة',
                'sort_order' => 2,
            ]);
        }
    }
}
