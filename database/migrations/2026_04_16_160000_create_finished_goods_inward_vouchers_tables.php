<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finished_goods_inward_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('voucher_number');
            $table->dateTime('voucher_date');
            $table->foreignId('credit_account_group_id')->nullable()->constrained('account_groups')->nullOnDelete();
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'voucher_number']);
            $table->index(['company_id', 'voucher_date']);
        });

        Schema::create('finished_goods_inward_voucher_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finished_goods_inward_voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_product_id')->nullable()->constrained('service_products')->nullOnDelete();
            $table->string('description')->nullable();
            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('finished_goods_inward_voucher_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finished_goods_inward_voucher_lines');
        Schema::dropIfExists('finished_goods_inward_vouchers');
    }
};
