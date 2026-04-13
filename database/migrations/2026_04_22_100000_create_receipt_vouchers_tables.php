<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('receipt_number');
            $table->date('receipt_date');
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('payment_method')->default('cash');
            $table->string('payment_kind')->default('advance');
            $table->foreignId('account_group_id')->nullable()->constrained('account_groups')->nullOnDelete();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'receipt_number']);
            $table->index(['company_id', 'receipt_date']);
        });

        Schema::create('receipt_voucher_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_voucher_id')->constrained('receipt_vouchers')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('receipt_voucher_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_voucher_lines');
        Schema::dropIfExists('receipt_vouchers');
    }
};
