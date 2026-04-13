<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_product_id')->nullable()->constrained('service_products')->nullOnDelete();
            $table->string('description')->nullable();
            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('inventory_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_order_lines');
    }
};
