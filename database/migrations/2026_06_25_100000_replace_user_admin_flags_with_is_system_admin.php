<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_system_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_system_admin')->default(false)->after('phone');
            });
        }

        if (Schema::hasColumn('users', 'is_main_user') || Schema::hasColumn('users', 'is_super_user')) {
            DB::table('users')
                ->where(function ($query) {
                    if (Schema::hasColumn('users', 'is_main_user')) {
                        $query->orWhere('is_main_user', true);
                    }
                    if (Schema::hasColumn('users', 'is_super_user')) {
                        $query->orWhere('is_super_user', true);
                    }
                })
                ->update(['is_system_admin' => true]);
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_super_user')) {
                $table->dropColumn('is_super_user');
            }
            if (Schema::hasColumn('users', 'is_main_user')) {
                $table->dropColumn('is_main_user');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_main_user')) {
                $table->boolean('is_main_user')->default(false)->after('phone');
            }
            if (! Schema::hasColumn('users', 'is_super_user')) {
                $table->boolean('is_super_user')->default(false)->after('is_main_user');
            }
        });

        if (Schema::hasColumn('users', 'is_system_admin')) {
            DB::table('users')
                ->where('is_system_admin', true)
                ->update([
                    'is_main_user' => true,
                    'is_super_user' => true,
                ]);
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_system_admin')) {
                $table->dropColumn('is_system_admin');
            }
        });
    }
};
