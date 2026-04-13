<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Support\UserPermissionRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * مستخدم إداري افتراضي + ربط بالشركة الأولى.
 *
 * يمكن تجاوز القيم عبر .env:
 * SEED_ADMIN_EMAIL, SEED_ADMIN_PASSWORD, SEED_ADMIN_NAME, SEED_ADMIN_USERNAME
 */
final class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! Company::query()->exists()) {
            $this->call(CompanySeeder::class);
        }

        $company = Company::query()->orderBy('id')->firstOrFail();

        $email = (string) env('SEED_ADMIN_EMAIL', 'mo.askary@gmail.com');
        $password = (string) env('SEED_ADMIN_PASSWORD', 'newpassword');
        $name = (string) env('SEED_ADMIN_NAME', 'Askary');

        $defaultUsername = str_replace(['.', '+', '@'], '', Str::before($email, '@')) ?: 'admin';
        $username = (string) env('SEED_ADMIN_USERNAME', $defaultUsername);

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'name_ar' => $name,
                'name_en' => $name,
                'username' => $username,
                'password' => Hash::make($password),
                'company_id' => $company->getKey(),
                'is_main_user' => true,
                'is_super_user' => true,
                'permissions' => UserPermissionRegistry::allKeys(),
            ],
        );
    }
}
