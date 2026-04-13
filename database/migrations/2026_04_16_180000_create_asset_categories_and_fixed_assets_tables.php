<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->decimal('annual_depreciation_rate', 8, 2)->default(0);
            $table->timestamps();

            $table->index('company_id');
        });

        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('asset_category_id')->constrained()->restrictOnDelete();
            $table->decimal('historical_cost', 15, 2)->default(0);
            $table->decimal('annual_depreciation_rate', 8, 2)->default(0);
            $table->date('usage_start_date')->nullable();
            $table->date('depreciation_start_date')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
        });

        $now = now();
        foreach (DB::table('companies')->pluck('id') as $companyId) {
            DB::table('asset_categories')->insert([
                'company_id' => $companyId,
                'name_ar' => 'تصنيف افتراضي',
                'annual_depreciation_rate' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
        Schema::dropIfExists('asset_categories');
    }
};
