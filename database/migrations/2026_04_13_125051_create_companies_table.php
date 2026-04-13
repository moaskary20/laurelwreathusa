<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name');
            $table->string('trade_name');
            $table->string('legal_type')->default('individual'); // مؤسسة فردية / ...
            $table->string('trade_category')->default('commercial'); // تجارية / ...
            $table->string('national_number')->nullable();
            $table->string('registration_number')->nullable();
            $table->unsignedInteger('sales_invoice_start')->default(1);
            $table->text('objectives')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('sales_tax_number')->nullable();
            $table->text('address')->nullable();
            $table->string('fax')->nullable();
            $table->string('po_box')->nullable();
            $table->date('fiscal_year_end')->nullable();
            $table->string('inventory_system')->default('perpetual');
            $table->string('inventory_pricing')->default('average');
            $table->string('logo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
