<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'name_ar')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('name_ar')->nullable()->after('name'));
        }
        if (! Schema::hasColumn('users', 'name_en')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('name_en')->nullable()->after('name_ar'));
        }
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('username')->nullable()->unique()->after('email'));
        }
        if (! Schema::hasColumn('users', 'phone')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('phone')->nullable()->after('username'));
        }
        if (! Schema::hasColumn('users', 'company_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('office_id')->constrained()->nullOnDelete();
            });
        }
        if (! Schema::hasColumn('users', 'is_main_user')) {
            Schema::table('users', fn (Blueprint $table) => $table->boolean('is_main_user')->default(false)->after('phone'));
        }
        if (! Schema::hasColumn('users', 'is_super_user')) {
            Schema::table('users', fn (Blueprint $table) => $table->boolean('is_super_user')->default(false)->after('is_main_user'));
        }
        if (! Schema::hasColumn('users', 'subscription_validity')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('subscription_validity')->nullable()->after('is_super_user'));
        }
        if (! Schema::hasColumn('users', 'permissions')) {
            Schema::table('users', fn (Blueprint $table) => $table->json('permissions')->nullable()->after('subscription_validity'));
        }

        if (Schema::hasTable('users') && Schema::hasTable('offices')) {
            foreach (DB::table('users')->whereNotNull('office_id')->whereNull('company_id')->cursor() as $row) {
                $companyId = DB::table('offices')->where('id', $row->office_id)->value('company_id');
                if ($companyId !== null) {
                    DB::table('users')->where('id', $row->id)->update(['company_id' => $companyId]);
                }
            }
        }

        foreach (DB::table('users')->whereNull('name_ar')->cursor() as $row) {
            DB::table('users')->where('id', $row->id)->update(['name_ar' => $row->name]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn([
                'name_ar',
                'name_en',
                'username',
                'phone',
                'is_main_user',
                'is_super_user',
                'subscription_validity',
                'permissions',
            ]);
        });
    }
};
