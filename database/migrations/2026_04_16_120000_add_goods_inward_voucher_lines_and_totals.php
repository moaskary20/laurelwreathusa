<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_inward_vouchers', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('voucher_date');
            $table->foreignId('currency_id')->nullable()->after('supplier_id')->constrained('currencies')->nullOnDelete();
            $table->decimal('subtotal_before_tax', 15, 2)->default(0)->after('purchase_order_id');
            $table->decimal('tax_total', 15, 2)->default(0)->after('subtotal_before_tax');
            $table->decimal('grand_total', 15, 2)->default(0)->after('tax_total');
            $table->string('total_in_words')->nullable()->after('grand_total');
        });

        Schema::create('goods_inward_voucher_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_inward_voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_product_id')->nullable()->constrained('service_products')->nullOnDelete();
            $table->string('description')->nullable();
            $table->string('unit_label')->nullable();
            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('amount_before_tax', 15, 2)->default(0);
            $table->decimal('tax_rate', 8, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('goods_inward_voucher_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_inward_voucher_lines');

        Schema::table('goods_inward_vouchers', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn([
                'due_date',
                'currency_id',
                'subtotal_before_tax',
                'tax_total',
                'grand_total',
                'total_in_words',
            ]);
        });
    }
};
