<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_products', function (Blueprint $table) {
            $table->decimal('stock_quantity', 15, 4)->default(0)->after('sale_price');
            $table->decimal('unit_cost', 15, 2)->default(0)->after('stock_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('service_products', function (Blueprint $table) {
            $table->dropColumn(['stock_quantity', 'unit_cost']);
        });
    }
};
