<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_requisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('request_number');
            $table->dateTime('request_date');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'request_number']);
            $table->index(['company_id', 'request_date']);
        });

        Schema::create('warehouse_requisition_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_requisition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_product_id')->nullable()->constrained('service_products')->nullOnDelete();
            $table->string('description')->nullable();
            $table->decimal('quantity', 15, 4)->default(1);
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('warehouse_requisition_id');
        });

        Schema::create('warehouse_outward_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('voucher_number');
            $table->dateTime('voucher_date');
            $table->foreignId('warehouse_requisition_id')->constrained('warehouse_requisitions')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'voucher_number']);
            $table->index(['company_id', 'voucher_date']);
        });

        Schema::create('warehouse_outward_voucher_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_outward_voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_product_id')->nullable()->constrained('service_products')->nullOnDelete();
            $table->string('description')->nullable();
            $table->decimal('quantity_requested', 15, 4)->default(0);
            $table->decimal('quantity_disbursed', 15, 4)->default(0);
            $table->decimal('difference', 15, 4)->default(0);
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('warehouse_outward_voucher_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_outward_voucher_lines');
        Schema::dropIfExists('warehouse_outward_vouchers');
        Schema::dropIfExists('warehouse_requisition_lines');
        Schema::dropIfExists('warehouse_requisitions');
    }
};
